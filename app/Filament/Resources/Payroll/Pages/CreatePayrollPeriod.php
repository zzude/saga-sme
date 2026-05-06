<?php
namespace App\Filament\Resources\Payroll\Pages;
use App\Filament\Resources\Payroll\PayrollPeriodResource;
use Filament\Resources\Pages\CreateRecord;
class CreatePayrollPeriod extends CreateRecord
{
    protected static string $resource = PayrollPeriodResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = auth()->user()->company_id;
        return $data;
    }
}
