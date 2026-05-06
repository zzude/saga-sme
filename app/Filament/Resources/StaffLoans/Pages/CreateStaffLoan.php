<?php

namespace App\Filament\Resources\StaffLoans\Pages;

use App\Filament\Resources\StaffLoans\StaffLoanResource;
use App\Services\StaffLoanService;
use Filament\Resources\Pages\CreateRecord;

class CreateStaffLoan extends CreateRecord
{
    protected static string $resource = StaffLoanResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['loan_no'] = app(StaffLoanService::class)->generateLoanNo($data['company_id']);
        return $data;
    }
}
