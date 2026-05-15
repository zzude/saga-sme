<?php

namespace App\Services;

use App\Models\AnnualBudget;
use App\Models\BudgetItem;
use App\Models\WarrantAllocation;
use App\Models\WarrantItem;
use App\Models\Virement;
use App\Models\VirementItem;
use App\Models\SupplementaryBudget;
use App\Models\GovernmentBankAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class BudgetService
{
    // ═══════════════════════════════════════════════════════════════
    // ANNUAL BUDGET
    // ═══════════════════════════════════════════════════════════════

    /**
     * Approve & activate an annual budget.
     */
    public function approveBudget(AnnualBudget $budget, ?string $notes = null): AnnualBudget
    {
        if (!in_array($budget->status, [
            AnnualBudget::STATUS_DRAFT,
            AnnualBudget::STATUS_SUBMITTED,
        ])) {
            throw new Exception("Bajet tidak boleh diluluskan — status semasa: {$budget->status}");
        }

        $budget->update([
            'status'         => AnnualBudget::STATUS_ACTIVE,
            'approved_by'    => Auth::id(),
            'approved_at'    => now(),
            'approval_notes' => $notes,
        ]);

        return $budget->fresh();
    }

    /**
     * Close a budget (end of financial year).
     */
    public function closeBudget(AnnualBudget $budget): AnnualBudget
    {
        if ($budget->status !== AnnualBudget::STATUS_ACTIVE) {
            throw new Exception("Hanya bajet aktif boleh ditutup.");
        }

        $budget->update(['status' => AnnualBudget::STATUS_CLOSED]);

        return $budget->fresh();
    }

    /**
     * Recalculate budget totals from items.
     */
    public function recalculateBudget(AnnualBudget $budget): void
    {
        $total     = $budget->budgetItems()->sum('original_amount');
        $revised   = $budget->budgetItems()->sum('revised_amount');
        $allocated = $budget->budgetItems()->sum('allocated_amount');
        $actual    = $budget->budgetItems()->sum('actual_amount');

        $budget->update([
            'total_amount'     => $total,
            'allocated_amount' => $allocated,
            'balance_amount'   => $revised - $allocated,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // WARRANT ALLOCATION
    // ═══════════════════════════════════════════════════════════════

    /**
     * Issue a warrant (draft → issued).
     * Validates each warrant item against budget item balance.
     */
    public function issueWarrant(WarrantAllocation $warrant, ?string $notes = null): WarrantAllocation
    {
        if ($warrant->status !== WarrantAllocation::STATUS_DRAFT) {
            throw new Exception("Waran perlu dalam status Draf untuk dikeluarkan.");
        }

        // Validate each item has sufficient budget balance
        $errors = [];
        foreach ($warrant->warrantItems as $item) {
            $bi = $item->budgetItem;
            if ($bi->balance_amount < $item->warrant_amount) {
                $errors[] = "Baki tidak mencukupi untuk '{$bi->description}': "
                    . "Diperlukan RM " . number_format($item->warrant_amount, 2)
                    . ", Baki RM " . number_format($bi->balance_amount, 2);
            }
        }

        if (!empty($errors)) {
            throw new Exception(implode("\n", $errors));
        }

        DB::transaction(function () use ($warrant, $notes) {
            // Deduct allocated_amount from each budget_item
            foreach ($warrant->warrantItems as $item) {
                $bi = $item->budgetItem;
                $bi->increment('allocated_amount', $item->warrant_amount);
                $bi->decrement('balance_amount', $item->warrant_amount);

                // Update warrant_item balance
                $item->update(['balance_amount' => $item->warrant_amount]);
            }

            // Update warrant totals
            $total = $warrant->warrantItems()->sum('warrant_amount');
            $warrant->update([
                'status'       => WarrantAllocation::STATUS_ISSUED,
                'total_amount' => $total,
                'balance_amount' => $total,
                'approved_by'  => Auth::id(),
                'approved_at'  => now(),
                'approval_notes' => $notes,
            ]);

            // Update parent budget
            $this->recalculateBudget($warrant->annualBudget);
        });

        return $warrant->fresh();
    }

    /**
     * Activate a warrant (issued → active).
     */
    public function activateWarrant(WarrantAllocation $warrant): WarrantAllocation
    {
        if ($warrant->status !== WarrantAllocation::STATUS_ISSUED) {
            throw new Exception("Waran perlu dalam status Dikeluarkan untuk diaktifkan.");
        }

        $warrant->update(['status' => WarrantAllocation::STATUS_ACTIVE]);

        return $warrant->fresh();
    }

    /**
     * Cancel a warrant — reverse all budget_item allocations.
     */
    public function cancelWarrant(WarrantAllocation $warrant, ?string $notes = null): WarrantAllocation
    {
        if (!in_array($warrant->status, [
            WarrantAllocation::STATUS_ISSUED,
            WarrantAllocation::STATUS_ACTIVE,
        ])) {
            throw new Exception("Hanya waran Dikeluarkan/Aktif boleh dibatalkan.");
        }

        DB::transaction(function () use ($warrant, $notes) {
            foreach ($warrant->warrantItems as $item) {
                $remaining = $item->balance_amount; // Unreleased portion
                if ($remaining > 0) {
                    $bi = $item->budgetItem;
                    $bi->decrement('allocated_amount', $remaining);
                    $bi->increment('balance_amount', $remaining);
                }
            }

            $warrant->update([
                'status'         => WarrantAllocation::STATUS_CANCELLED,
                'approval_notes' => $notes,
            ]);

            $this->recalculateBudget($warrant->annualBudget);
        });

        return $warrant->fresh();
    }

    // ═══════════════════════════════════════════════════════════════
    // VIREMENT
    // ═══════════════════════════════════════════════════════════════

    /**
     * Submit virement for approval.
     */
    public function submitVirement(Virement $virement): Virement
    {
        if ($virement->status !== Virement::STATUS_DRAFT) {
            throw new Exception("Virement perlu dalam status Draf untuk dikemukakan.");
        }

        // Validate FROM items balance
        $errors = $virement->validateBalance();
        if (!empty($errors)) {
            throw new Exception(implode("\n", $errors));
        }

        // Validate FROM total == TO total (must balance)
        $fromTotal = $virement->fromItems()->sum('amount');
        $toTotal   = $virement->toItems()->sum('amount');

        if (round($fromTotal, 2) !== round($toTotal, 2)) {
            throw new Exception(
                "Jumlah FROM (RM " . number_format($fromTotal, 2) . ") "
                . "tidak sama dengan jumlah TO (RM " . number_format($toTotal, 2) . "). "
                . "Virement mesti seimbang."
            );
        }

        $virement->update([
            'status'       => Virement::STATUS_PENDING,
            'total_amount' => $fromTotal,
            'prepared_by'  => Auth::id(),
        ]);

        return $virement->fresh();
    }

    /**
     * Approve and post a virement.
     */
    public function approveAndPostVirement(Virement $virement, ?string $notes = null): Virement
    {
        if ($virement->status !== Virement::STATUS_PENDING) {
            throw new Exception("Virement perlu dalam status Menunggu Kelulusan.");
        }

        // Re-validate balance (might have changed)
        $errors = $virement->validateBalance();
        if (!empty($errors)) {
            throw new Exception(implode("\n", $errors));
        }

        DB::transaction(function () use ($virement, $notes) {
            // Deduct FROM items
            foreach ($virement->fromItems as $item) {
                $bi = $item->budgetItem;
                $bi->decrement('revised_amount', $item->amount);
                $bi->decrement('balance_amount', $item->amount);
            }

            // Add TO items
            foreach ($virement->toItems as $item) {
                $bi = $item->budgetItem;
                $bi->increment('revised_amount', $item->amount);
                $bi->increment('balance_amount', $item->amount);
            }

            $virement->update([
                'status'         => Virement::STATUS_POSTED,
                'approved_by'    => Auth::id(),
                'approved_at'    => now(),
                'approval_notes' => $notes,
            ]);

            $this->recalculateBudget($virement->annualBudget);
        });

        return $virement->fresh();
    }

    /**
     * Reject a virement.
     */
    public function rejectVirement(Virement $virement, string $notes): Virement
    {
        if ($virement->status !== Virement::STATUS_PENDING) {
            throw new Exception("Hanya virement Menunggu Kelulusan boleh ditolak.");
        }

        $virement->update([
            'status'         => Virement::STATUS_REJECTED,
            'approved_by'    => Auth::id(),
            'approved_at'    => now(),
            'approval_notes' => $notes,
        ]);

        return $virement->fresh();
    }

    // ═══════════════════════════════════════════════════════════════
    // SUPPLEMENTARY BUDGET
    // ═══════════════════════════════════════════════════════════════

    /**
     * Approve and post a supplementary budget.
     */
    public function approveAndPostSupplementary(
        SupplementaryBudget $supplementary,
        ?string $notes = null
    ): SupplementaryBudget {
        if ($supplementary->status !== SupplementaryBudget::STATUS_SUBMITTED) {
            throw new Exception("Tambahan Peruntukan perlu dalam status Dikemukakan.");
        }

        DB::transaction(function () use ($supplementary, $notes) {
            $bi = $supplementary->budgetItem;

            // Add to budget item
            $bi->increment('original_amount', $supplementary->amount);
            $bi->increment('revised_amount', $supplementary->amount);
            $bi->increment('balance_amount', $supplementary->amount);

            $supplementary->update([
                'status'         => SupplementaryBudget::STATUS_POSTED,
                'approved_by'    => Auth::id(),
                'approved_at'    => now(),
                'approval_notes' => $notes,
            ]);

            // Recalculate parent budget
            $this->recalculateBudget($supplementary->annualBudget);
        });

        return $supplementary->fresh();
    }

    /**
     * Reject a supplementary budget.
     */
    public function rejectSupplementary(SupplementaryBudget $supplementary, string $notes): SupplementaryBudget
    {
        if ($supplementary->status !== SupplementaryBudget::STATUS_SUBMITTED) {
            throw new Exception("Hanya tambahan yang Dikemukakan boleh ditolak.");
        }

        $supplementary->update([
            'status'         => SupplementaryBudget::STATUS_REJECTED,
            'approved_by'    => Auth::id(),
            'approved_at'    => now(),
            'approval_notes' => $notes,
        ]);

        return $supplementary->fresh();
    }

    // ═══════════════════════════════════════════════════════════════
    // GOVERNMENT BANK ACCOUNT — Real-time Balance
    // ═══════════════════════════════════════════════════════════════

    /**
     * Refresh balance for ALL active gov bank accounts from GL.
     */
    public function refreshAllBankBalances(int $companyId): void
    {
        $accounts = GovernmentBankAccount::forCompany($companyId)->active()->get();

        foreach ($accounts as $account) {
            $this->refreshBankBalance($account);
        }
    }

    /**
     * Refresh balance for a single gov bank account from GL.
     */
    public function refreshBankBalance(GovernmentBankAccount $govAccount): void
    {
        // Debit side (cash in)
        $dr = DB::table('journal_lines')
            ->where('account_id', $govAccount->account_id)
            ->where('company_id', $govAccount->company_id)
            ->whereIn('journal_header_id', function ($q) {
                $q->select('id')
                    ->from('journal_headers')
                    ->where('status', 'posted');
            })
            ->sum('debit_amount');

        // Credit side (cash out)
        $cr = DB::table('journal_lines')
            ->where('account_id', $govAccount->account_id)
            ->where('company_id', $govAccount->company_id)
            ->whereIn('journal_header_id', function ($q) {
                $q->select('id')
                    ->from('journal_headers')
                    ->where('status', 'posted');
            })
            ->sum('credit_amount');

        $govAccount->update([
            'current_balance'    => $dr - $cr,
            'balance_updated_at' => now(),
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // BUDGET UTILISATION SUMMARY
    // ═══════════════════════════════════════════════════════════════

    /**
     * Returns utilisation summary per object_class for a given budget.
     */
    public function getUtilisationSummary(AnnualBudget $budget): array
    {
        $items = $budget->budgetItems()
            ->select('object_class',
                DB::raw('SUM(original_amount) as original'),
                DB::raw('SUM(revised_amount) as revised'),
                DB::raw('SUM(allocated_amount) as allocated'),
                DB::raw('SUM(actual_amount) as actual'),
                DB::raw('SUM(balance_amount) as balance')
            )
            ->groupBy('object_class')
            ->get();

        return $items->map(function ($row) {
            $utilisasi = $row->revised > 0
                ? round(($row->actual / $row->revised) * 100, 2)
                : 0;

            return [
                'object_class' => $row->object_class,
                'label'        => BudgetItem::OBJECT_CLASSES[$row->object_class] ?? $row->object_class,
                'original'     => $row->original,
                'revised'      => $row->revised,
                'allocated'    => $row->allocated,
                'actual'       => $row->actual,
                'balance'      => $row->balance,
                'utilisation'  => $utilisasi,
            ];
        })->toArray();
    }
}
