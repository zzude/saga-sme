<?php

namespace App\Filament\Resources\AnnualBudgetResource\Pages;

use App\Filament\Resources\AnnualBudgetResource;
use App\Models\AnnualBudget;
use App\Models\BudgetItem;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateAnnualBudget extends CreateRecord
{
    protected static string $resource = AnnualBudgetResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id']  = Auth::user()?->company_id ?? 1;
        $data['prepared_by'] = Auth::id();

        if (empty($data['budget_no'])) {
            $data['budget_no'] = AnnualBudget::generateBudgetNo(
                $data['company_id'],
                $data['financial_year']
            );
        }

        // Remove items from data — handle manually in afterCreate
        unset($data['budgetItems']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $items = $this->data['budgetItems'] ?? [];

        foreach ($items as $item) {
            BudgetItem::create([
                'annual_budget_id' => $this->record->id,
                'company_id'       => $this->record->company_id,
                'account_id'       => $item['account_id'] ?? null,
                'object_class'     => $item['object_class'] ?? null,
                'object_code'      => $item['object_code'] ?? null,
                'description'      => $item['description'] ?? '',
                'original_amount'  => $item['original_amount'] ?? 0,
                'revised_amount'   => $item['original_amount'] ?? 0,
                'balance_amount'   => $item['original_amount'] ?? 0,
                'sort_order'       => $item['sort_order'] ?? 0,
                'notes'            => $item['notes'] ?? null,
            ]);
        }

        $this->record->recalculateTotals();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
