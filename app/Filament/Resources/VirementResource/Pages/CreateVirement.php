<?php

namespace App\Filament\Resources\VirementResource\Pages;

use App\Filament\Resources\VirementResource;
use App\Models\Virement;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;


class CreateVirement extends CreateRecord
{
    protected static string $resource = VirementResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id']  = Auth::user()?->company_id ?? 1;
        $data['prepared_by'] = Auth::id();
        if (empty($data['virement_no'])) {
            $budget = \App\Models\AnnualBudget::find($data['annual_budget_id']);
            $data['virement_no'] = Virement::generateVirementNo(
                $data['company_id'],
                $budget?->financial_year ?? now()->year
            );
        }
        return $data;
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}