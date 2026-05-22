<?php

namespace App\Services;

use App\Models\FixedAsset;
use App\Models\AssetDepreciation;
use App\Models\AssetCategory;
use App\Models\AccountingPeriod;
use App\Models\JournalHeader;
use App\Models\JournalLine;
use Illuminate\Support\Facades\DB;

class FixedAssetService
{
    // -------------------------------------------------------------------------
    // 1. CREATE ASSET (with optional purchase journal)
    // -------------------------------------------------------------------------
    public function create(array $data, bool $postJournal = true): FixedAsset
    {
        return DB::transaction(function () use ($data, $postJournal) {

            $companyId = auth()->user()->company_id;

            $asset = FixedAsset::create([
                'company_id'               => $companyId,
                'asset_no'                 => FixedAsset::generateAssetNo(),
                'name'                     => $data['name'],
                'description'              => $data['description'] ?? null,
                'category_id'              => $data['category_id'],
                'purchase_date'            => $data['purchase_date'],
                'purchase_amount'          => $data['purchase_amount'],
                'salvage_value'            => $data['salvage_value'] ?? 0,
                'useful_life_years'        => $data['useful_life_years'],
                'depreciation_method'      => $data['depreciation_method'],
                'vendor_id'                => $data['vendor_id'] ?? null,
                'vendor_invoice_no'        => $data['vendor_invoice_no'] ?? null,
                'location'                 => $data['location'] ?? null,
                'assigned_to'              => $data['assigned_to'] ?? null,
                'current_book_value'       => $data['purchase_amount'],
                'accumulated_depreciation' => 0,
                'status'                   => 'active',
            ]);

            // Post purchase journal if category has COA configured
            if ($postJournal) {
                $category = $asset->category;

                if ($category->asset_account_id) {
                    $journal = $this->postPurchaseJournal($asset, $category, $companyId);
                    $asset->update(['purchase_journal_id' => $journal->id]);
                }
            }

            return $asset;
        });
    }

    // -------------------------------------------------------------------------
    // 2. RUN DEPRECIATION for one asset, one period
    // -------------------------------------------------------------------------
    public function depreciate(FixedAsset $asset, string $depreciationDate, ?int $periodId = null): AssetDepreciation
    {
        if ($asset->status !== 'active') {
            throw new \RuntimeException("Aset {$asset->asset_no} tidak aktif.");
        }

        if ($asset->isFullyDepreciated()) {
            throw new \RuntimeException("Aset {$asset->asset_no} sudah habis susut nilai.");
        }

        // Check duplicate — one depreciation per period per asset
        if ($periodId) {
            $exists = AssetDepreciation::where('asset_id', $asset->id)
                ->where('period_id', $periodId)
                ->exists();

            if ($exists) {
                throw new \RuntimeException("Susut nilai untuk tempoh ini sudah diproses.");
            }
        }

        return DB::transaction(function () use ($asset, $depreciationDate, $periodId) {

            $companyId    = auth()->user()->company_id;
            $amount       = $asset->monthlyDepreciation();
            $newBookValue = round($asset->current_book_value - $amount, 2);

            // Don't go below salvage value
            if ($newBookValue < $asset->salvage_value) {
                $amount       = round($asset->current_book_value - $asset->salvage_value, 2);
                $newBookValue = $asset->salvage_value;
            }

            // Post depreciation journal
            $category = $asset->category;
            $journal  = null;

            if ($category->depreciation_expense_account_id && $category->accumulated_depreciation_account_id) {
                $journal = $this->postDepreciationJournal($asset, $category, $amount, $depreciationDate, $companyId);
            }

            // Record depreciation
            $depreciation = AssetDepreciation::create([
                'company_id'        => $companyId,
                'asset_id'          => $asset->id,
                'period_id'         => $periodId,
                'depreciation_date' => $depreciationDate,
                'amount'            => $amount,
                'book_value_after'  => $newBookValue,
                'journal_id'        => $journal?->id,
            ]);

            // Update asset current values
            $asset->update([
                'current_book_value'       => $newBookValue,
                'accumulated_depreciation' => round($asset->accumulated_depreciation + $amount, 2),
            ]);

            return $depreciation;
        });
    }

    // -------------------------------------------------------------------------
    // 3. RUN DEPRECIATION for ALL active assets in a period
    // -------------------------------------------------------------------------
    public function depreciateAll(string $depreciationDate, ?int $periodId = null): array
    {
        $companyId = auth()->user()->company_id;

        $assets = FixedAsset::where('company_id', $companyId)
            ->where('status', 'active')
            ->where('purchase_date', '<=', $depreciationDate)
            ->get()
            ->filter(fn ($a) => !$a->isFullyDepreciated());

        $results = ['success' => [], 'skipped' => [], 'errors' => []];

        foreach ($assets as $asset) {
            try {
                $dep = $this->depreciate($asset, $depreciationDate, $periodId);
                $results['success'][] = [
                    'asset_no' => $asset->asset_no,
                    'name'     => $asset->name,
                    'amount'   => $dep->amount,
                ];
            } catch (\RuntimeException $e) {
                $results['skipped'][] = [
                    'asset_no' => $asset->asset_no,
                    'reason'   => $e->getMessage(),
                ];
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'asset_no' => $asset->asset_no,
                    'error'    => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    // -------------------------------------------------------------------------
    // 4. DISPOSE ASSET
    // -------------------------------------------------------------------------
    public function dispose(FixedAsset $asset, string $disposalDate, float $proceeds = 0): FixedAsset
    {
        if ($asset->status !== 'active') {
            throw new \RuntimeException("Aset {$asset->asset_no} tidak aktif.");
        }

        return DB::transaction(function () use ($asset, $disposalDate, $proceeds) {

            $companyId = auth()->user()->company_id;
            $category  = $asset->category;

            // Post disposal journal
            $journal = null;
            if ($category->asset_account_id && $category->accumulated_depreciation_account_id) {
                $journal = $this->postDisposalJournal($asset, $category, $proceeds, $disposalDate, $companyId);
            }

            $asset->update([
                'status'              => 'disposed',
                'disposed_at'         => $disposalDate,
                'disposal_proceeds'   => $proceeds,
                'disposal_journal_id' => $journal?->id,
                'current_book_value'  => 0,
            ]);

            return $asset;
        });
    }

    // =========================================================================
    // PRIVATE — Journal helpers
    // =========================================================================

    private function postPurchaseJournal(FixedAsset $asset, AssetCategory $category, int $companyId): JournalHeader
    {
        $journal = JournalHeader::create([
            'company_id'  => $companyId,
            'type'        => 'FA',
            'date'        => $asset->purchase_date,
            'description' => "Pembelian Aset: {$asset->name} ({$asset->asset_no})",
            'status'      => 'posted',
            'posted_at'   => now(),
            'created_by'  => auth()->id(),
        ]);

        // DR Fixed Asset
        JournalLine::create([
            'company_id'    => $companyId,
            'journal_id'    => $journal->id,
            'account_id'    => $category->asset_account_id,
            'description'   => "Pembelian: {$asset->name}",
            'debit'         => $asset->purchase_amount,
            'credit'        => 0,
            'base_debit'    => $asset->purchase_amount,
            'base_credit'   => 0,
            'currency_code' => 'MYR',
            'exchange_rate' => 1,
        ]);

        // CR Bank/AP — use account 1100 (Cash at Bank) as default
        // TODO: allow user to select payment account
        $bankAccountId = \App\Models\Account::where('company_id', $companyId)
            ->where('code', '1100')
            ->value('id');

        JournalLine::create([
            'company_id'    => $companyId,
            'journal_id'    => $journal->id,
            'account_id'    => $bankAccountId,
            'description'   => "Bayaran: {$asset->name}",
            'debit'         => 0,
            'credit'        => $asset->purchase_amount,
            'base_debit'    => 0,
            'base_credit'   => $asset->purchase_amount,
            'currency_code' => 'MYR',
            'exchange_rate' => 1,
        ]);

        return $journal;
    }

    private function postDepreciationJournal(FixedAsset $asset, AssetCategory $category, float $amount, string $date, int $companyId): JournalHeader
    {
        $journal = JournalHeader::create([
            'company_id'  => $companyId,
            'type'        => 'FA',
            'date'        => $date,
            'description' => "Susut Nilai: {$asset->name} ({$asset->asset_no})",
            'status'      => 'posted',
            'posted_at'   => now(),
            'created_by'  => auth()->id(),
        ]);

        // DR Depreciation Expense
        JournalLine::create([
            'company_id'    => $companyId,
            'journal_id'    => $journal->id,
            'account_id'    => $category->depreciation_expense_account_id,
            'description'   => "Susut Nilai: {$asset->name}",
            'debit'         => $amount,
            'credit'        => 0,
            'base_debit'    => $amount,
            'base_credit'   => 0,
            'currency_code' => 'MYR',
            'exchange_rate' => 1,
        ]);

        // CR Accumulated Depreciation
        JournalLine::create([
            'company_id'    => $companyId,
            'journal_id'    => $journal->id,
            'account_id'    => $category->accumulated_depreciation_account_id,
            'description'   => "Susut Nilai Terkumpul: {$asset->name}",
            'debit'         => 0,
            'credit'        => $amount,
            'base_debit'    => 0,
            'base_credit'   => $amount,
            'currency_code' => 'MYR',
            'exchange_rate' => 1,
        ]);

        return $journal;
    }

    private function postDisposalJournal(FixedAsset $asset, AssetCategory $category, float $proceeds, string $date, int $companyId): JournalHeader
    {
        $journal = JournalHeader::create([
            'company_id'  => $companyId,
            'type'        => 'FA',
            'date'        => $date,
            'description' => "Pelupusan Aset: {$asset->name} ({$asset->asset_no})",
            'status'      => 'posted',
            'posted_at'   => now(),
            'created_by'  => auth()->id(),
        ]);

        $bookValue = $asset->current_book_value;
        $gainLoss  = round($proceeds - $bookValue, 2);

        // DR Accumulated Depreciation (clear it)
        JournalLine::create([
            'company_id'    => $companyId,
            'journal_id'    => $journal->id,
            'account_id'    => $category->accumulated_depreciation_account_id,
            'description'   => "Hapus Susut Nilai Terkumpul: {$asset->name}",
            'debit'         => $asset->accumulated_depreciation,
            'credit'        => 0,
            'base_debit'    => $asset->accumulated_depreciation,
            'base_credit'   => 0,
            'currency_code' => 'MYR',
            'exchange_rate' => 1,
        ]);

        // DR Cash (proceeds received)
        if ($proceeds > 0) {
            $bankAccountId = \App\Models\Account::where('company_id', $companyId)
                ->where('code', '1100')->value('id');

            JournalLine::create([
                'company_id'    => $companyId,
                'journal_id'    => $journal->id,
                'account_id'    => $bankAccountId,
                'description'   => "Hasil Pelupusan: {$asset->name}",
                'debit'         => $proceeds,
                'credit'        => 0,
                'base_debit'    => $proceeds,
                'base_credit'   => 0,
                'currency_code' => 'MYR',
                'exchange_rate' => 1,
            ]);
        }

        // CR Fixed Asset (remove cost)
        JournalLine::create([
            'company_id'    => $companyId,
            'journal_id'    => $journal->id,
            'account_id'    => $category->asset_account_id,
            'description'   => "Hapus Kos Aset: {$asset->name}",
            'debit'         => 0,
            'credit'        => $asset->purchase_amount,
            'base_debit'    => 0,
            'base_credit'   => $asset->purchase_amount,
            'currency_code' => 'MYR',
            'exchange_rate' => 1,
        ]);

        // Gain or Loss on disposal
        if ($gainLoss != 0) {
            $gainLossAccountCode = $gainLoss > 0 ? '7200' : '8200'; // Other Income / Other Expense
            $gainLossAccountId   = \App\Models\Account::where('company_id', $companyId)
                ->where('code', $gainLossAccountCode)->value('id');

            if ($gainLossAccountId) {
                JournalLine::create([
                    'company_id'    => $companyId,
                    'journal_id'    => $journal->id,
                    'account_id'    => $gainLossAccountId,
                    'description'   => $gainLoss > 0
                        ? "Untung Pelupusan: {$asset->name}"
                        : "Rugi Pelupusan: {$asset->name}",
                    'debit'         => $gainLoss < 0 ? abs($gainLoss) : 0,
                    'credit'        => $gainLoss > 0 ? $gainLoss : 0,
                    'base_debit'    => $gainLoss < 0 ? abs($gainLoss) : 0,
                    'base_credit'   => $gainLoss > 0 ? $gainLoss : 0,
                    'currency_code' => 'MYR',
                    'exchange_rate' => 1,
                ]);
            }
        }

        return $journal;
    }
}
