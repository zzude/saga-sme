<?php

// ─── CreateWarrantAllocation.php ─────────────────────────────────
namespace App\Filament\Resources\WarrantAllocationResource\Pages;

use App\Filament\Resources\WarrantAllocationResource;
use App\Models\WarrantAllocation;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateWarrantAllocation extends CreateRecord
{
    protected static string $resource = WarrantAllocationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = Auth::user()?->company_id ?? 1;
        $data['issued_by']  = Auth::id();

        if (empty($data['warrant_no'])) {
            $budget = \App\Models\AnnualBudget::find($data['annual_budget_id']);
            $data['warrant_no'] = WarrantAllocation::generateWarrantNo(
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