<?php

namespace App\Filament\Resources\SupplementaryBudgetResource\Pages;

use App\Filament\Resources\SupplementaryBudgetResource;
use App\Models\SupplementaryBudget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class CreateSupplementaryBudget extends CreateRecord
{
    protected static string $resource = SupplementaryBudgetResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id']  = Auth::user()?->company_id ?? 1;
        $data['prepared_by'] = Auth::id();
        if (empty($data['supplementary_no'])) {
            $budget = \App\Models\AnnualBudget::find($data['annual_budget_id']);
            $data['supplementary_no'] = SupplementaryBudget::generateNo(
                $data['company_id'],
                $budget?->financial_year ?? now()->year
            );
        }
        return $data;
    }
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
}