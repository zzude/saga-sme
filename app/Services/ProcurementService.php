<?php

namespace App\Services;

use App\Models\PurchaseRequisition;
use App\Models\SebutHarga;
use App\Models\Tender;
use App\Models\LocalOrder;
use App\Models\GoodsReceivedNote;
use App\Models\ProcurementContract;
use App\Models\BudgetItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ProcurementService
{
    // =========================================================================
    // PURCHASE REQUISITION
    // =========================================================================

    public function createPr(array $data): PurchaseRequisition
    {
        return DB::transaction(function () use ($data) {
            $data['pr_number']    = PurchaseRequisition::generatePrNumber();
            $data['company_id']   = Auth::user()->company_id;
            $data['requested_by'] = Auth::id();
            $data['status']       = 'draft';

            // Auto-set procurement method based on estimated amount
            $data['procurement_method'] = PurchaseRequisition::determineProcurementMethod(
                $data['estimated_amount'] ?? 0
            );

            $pr = PurchaseRequisition::create($data);

            // Create PR items
            foreach ($data['items'] ?? [] as $i => $item) {
                $pr->items()->create(array_merge($item, [
                    'company_id' => $pr->company_id,
                    'line_no'    => $i + 1,
                ]));
            }

            return $pr;
        });
    }

    public function submitPr(PurchaseRequisition $pr): void
    {
        if ($pr->status !== 'draft') {
            throw new \RuntimeException('Hanya PR status draf boleh dihantar.');
        }
        $pr->update(['status' => 'submitted']);
    }

    public function approvePrHod(PurchaseRequisition $pr): void
    {
        if ($pr->status !== 'submitted') {
            throw new \RuntimeException('PR belum dihantar untuk kelulusan.');
        }
        $pr->update([
            'status'           => 'approved_hod',
            'approved_by_hod'  => Auth::id(),
            'approved_hod_at'  => now(),
        ]);
    }

    public function approvePrFinance(PurchaseRequisition $pr): void
    {
        if ($pr->status !== 'approved_hod') {
            throw new \RuntimeException('PR belum lulus Ketua Jabatan.');
        }

        // Check budget availability
        if ($pr->budget_item_id) {
            $this->assertBudgetAvailable($pr->budget_item_id, $pr->estimated_amount);
        }

        $pr->update([
            'status'               => 'approved_finance',
            'approved_by_finance'  => Auth::id(),
            'approved_finance_at'  => now(),
        ]);
    }

    // =========================================================================
    // SEBUT HARGA
    // =========================================================================

    public function createSebutHarga(PurchaseRequisition $pr, array $data): SebutHarga
    {
        if (!$pr->canBeConverted()) {
            throw new \RuntimeException('PR belum mendapat kelulusan kewangan.');
        }

        return DB::transaction(function () use ($pr, $data) {
            $sh = SebutHarga::create(array_merge($data, [
                'company_id'               => Auth::user()->company_id,
                'sh_number'                => SebutHarga::generateShNumber(),
                'purchase_requisition_id'  => $pr->id,
                'budget_item_id'           => $pr->budget_item_id,
                'estimated_amount'         => $pr->estimated_amount,
                'created_by'               => Auth::id(),
                'status'                   => 'draft',
            ]));

            $pr->update(['status' => 'converted_sh']);

            return $sh;
        });
    }

    public function awardSebutHarga(SebutHarga $sh, int $vendorId, float $amount, string $justification): void
    {
        if ($sh->status !== 'evaluated') {
            throw new \RuntimeException('Sebut Harga belum dinilai.');
        }

        if (!$sh->meets_min_quotations) {
            throw new \RuntimeException(
                "Minimum {$sh->min_quotations} sebut harga diperlukan. Hanya {$sh->quotation_count} diterima."
            );
        }

        DB::transaction(function () use ($sh, $vendorId, $amount, $justification) {
            // Mark awarded quotation
            $sh->quotations()->update(['is_awarded' => false]);
            $sh->quotations()->where('vendor_id', $vendorId)->update(['is_awarded' => true]);

            $sh->update([
                'awarded_vendor_id'  => $vendorId,
                'awarded_amount'     => $amount,
                'awarded_date'       => now()->toDateString(),
                'award_justification'=> $justification,
                'approved_by'        => Auth::id(),
                'approved_at'        => now(),
                'status'             => 'approved',
            ]);
        });
    }

    // =========================================================================
    // LOCAL ORDER
    // =========================================================================

    /**
     * Convert approved SH / direct PR / tender award → Local Order.
     * On approval: encumbrance committed to budget_item.
     */
    public function createLocalOrder(array $data, ?SebutHarga $sh = null, ?Tender $tender = null, ?PurchaseRequisition $pr = null): LocalOrder
    {
        return DB::transaction(function () use ($data, $sh, $tender, $pr) {

            $lo = LocalOrder::create(array_merge($data, [
                'company_id'              => Auth::user()->company_id,
                'lo_number'               => LocalOrder::generateLoNumber(),
                'sebut_harga_id'          => $sh?->id,
                'tender_id'               => $tender?->id,
                'purchase_requisition_id' => $pr?->id,
                'created_by'              => Auth::id(),
                'status'                  => 'draft',
            ]));

            foreach ($data['items'] ?? [] as $i => $item) {
                $lo->items()->create(array_merge($item, [
                    'company_id' => $lo->company_id,
                    'line_no'    => $i + 1,
                ]));
            }

            return $lo;
        });
    }

    /**
     * Approve LO → commit encumbrance on budget_item.
     */
    public function approveLocalOrder(LocalOrder $lo): void
    {
        if ($lo->status !== 'draft') {
            throw new \RuntimeException('LO bukan dalam status draf.');
        }

        DB::transaction(function () use ($lo) {
            $lo->update([
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            // Commit encumbrance — balance locked for this LO
            $lo->commitEncumbrance();

            $lo->update(['status' => 'issued']);
        });
    }

    // =========================================================================
    // GOODS RECEIVED NOTE
    // =========================================================================

    public function createGrn(LocalOrder $lo, array $data): GoodsReceivedNote
    {
        if (!in_array($lo->status, ['issued', 'partial_received'])) {
            throw new \RuntimeException('LO belum dikeluarkan kepada pembekal.');
        }

        return DB::transaction(function () use ($lo, $data) {
            $grn = GoodsReceivedNote::create(array_merge($data, [
                'company_id'     => Auth::user()->company_id,
                'grn_number'     => GoodsReceivedNote::generateGrnNumber(),
                'local_order_id' => $lo->id,
                'vendor_id'      => $lo->vendor_id,
                'received_by'    => Auth::id(),
                'status'         => 'draft',
            ]));

            foreach ($data['items'] ?? [] as $i => $item) {
                $grn->items()->create(array_merge($item, [
                    'company_id' => $grn->company_id,
                    'line_no'    => $i + 1,
                ]));
            }

            return $grn;
        });
    }

    /**
     * Verify + Post GRN:
     * - Frees encumbrance from budget_item
     * - Increases actual_spent on budget_item
     * - Creates GL journal (Dr Expense / Cr Payable)
     */
    public function postGrn(GoodsReceivedNote $grn): void
    {
        if ($grn->status !== 'verified') {
            throw new \RuntimeException('GRN belum disahkan oleh stor.');
        }

        DB::transaction(function () use ($grn) {
            $grn->post(Auth::id());

            // GL Journal
            $this->createGrnJournal($grn);
        });
    }

    // =========================================================================
    // GL JOURNAL for GRN
    // =========================================================================

    protected function createGrnJournal(GoodsReceivedNote $grn): void
    {
        $lo = $grn->localOrder;

        // Determine expense COA from objek_sebagai on LO
        // OS 22000 = Bekalan & Bahan Mentah
        // OS 23000 = Perkhidmatan Ikhtisas & Lain-lain Perkhidmatan
        // OS 29000 = Aset dll
        // For now: stub — BudgetClassificationService will map OS → COA
        $expenseCoa   = $this->mapObjekSebasaiToCoa($lo->objek_sebagai ?? '22000');
        $payableCoa   = '2100'; // Akaun Belum Bayar Pembekal

        $journalData = [
            'company_id'    => $grn->company_id,
            'journal_date'  => $grn->received_date,
            'reference'     => $grn->grn_number,
            'description'   => "GRN: {$grn->grn_number} — LO: {$lo->lo_number}",
            'source'        => 'grn',
            'source_id'     => $grn->id,
            'created_by'    => $grn->posted_by,
            'entries'       => [
                [
                    'coa_code'    => $expenseCoa,
                    'debit'       => $grn->total_received_amount,
                    'credit'      => 0,
                    'description' => "Belian: {$lo->lo_number}",
                ],
                [
                    'coa_code'    => $payableCoa,
                    'debit'       => 0,
                    'credit'      => $grn->total_received_amount,
                    'description' => "Hutang: {$lo->vendor_name}",
                ],
            ],
        ];

        // Use existing JournalService (from Kategori GL)
        $journal = app(JournalService::class)->post($journalData);

        $grn->update(['journal_id' => $journal->id]);
    }

    protected function mapObjekSebagaiToCoa(string $os): string
    {
        return match (true) {
            str_starts_with($os, '21') => '5100', // Emolumen
            str_starts_with($os, '22') => '5200', // Bekalan & Bahan
            str_starts_with($os, '23') => '5300', // Perkhidmatan
            str_starts_with($os, '24') => '5400', // Aset Alih
            str_starts_with($os, '28') => '5500', // Pemberian & Kenaan Tetap
            str_starts_with($os, '29') => '5600', // Perbelanjaan Lain
            default                    => '5200',
        };
    }

    // =========================================================================
    // BUDGET CHECK HELPER
    // =========================================================================

    public function assertBudgetAvailable(int $budgetItemId, float $amount): void
    {
        $budgetItem = BudgetItem::lockForUpdate()->findOrFail($budgetItemId);

        // True available = balance - already encumbered
        $available = (float) $budgetItem->balance_amount - (float) $budgetItem->encumbered_amount;

        if ($amount > $available) {
            throw new \RuntimeException(
                sprintf(
                    'Peruntukan tidak mencukupi. Baki tersedia: RM %s, Diperlukan: RM %s',
                    number_format($available, 2),
                    number_format($amount, 2)
                )
            );
        }
    }

    /**
     * Get real-time budget position for a budget_item.
     * Called by dashboard widgets and LO form.
     */
    public function getBudgetPosition(BudgetItem $item): array
    {
        return [
            'allocated'    => (float) $item->allocated_amount,
            'spent'        => (float) $item->actual_spent,
            'encumbered'   => (float) $item->encumbered_amount,
            'balance'      => (float) $item->balance_amount,
            'available'    => (float) $item->balance_amount - (float) $item->encumbered_amount,
            'utilisation'  => $item->allocated_amount > 0
                ? round((($item->actual_spent + $item->encumbered_amount) / $item->allocated_amount) * 100, 2)
                : 0,
        ];
    }
}
