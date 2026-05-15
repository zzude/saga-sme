<?php

namespace App\Filament\Resources\AnnualBudgetResource\Pages;

use App\Filament\Resources\AnnualBudgetResource;
use App\Models\BudgetItem;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAnnualBudget extends EditRecord
{
    protected static string $resource = AnnualBudgetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    // Load existing budget items into Repeater
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['budgetItems'] = BudgetItem::where('annual_budget_id', $this->record->id)
            ->orderBy('sort_order')
            ->get()
            ->map(fn($item) => [
                'account_id'      => $item->account_id,
                'object_class'    => $item->object_class,
                'object_code'     => $item->object_code,
                'description'     => $item->description,
                'original_amount' => $item->original_amount,
                'sort_order'      => $item->sort_order,
                'notes'           => $item->notes,
            ])
            ->toArray();

        return $data;
    }

    // Sync budget items on save
    protected function afterSave(): void
    {
        $items = $this->data['budgetItems'] ?? [];

        // Delete existing and recreate
        BudgetItem::where('annual_budget_id', $this->record->id)->delete();

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
