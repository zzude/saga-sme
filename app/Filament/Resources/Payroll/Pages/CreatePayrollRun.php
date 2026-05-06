<?php
namespace App\Filament\Resources\Payroll\Pages;
use App\Filament\Resources\Payroll\PayrollRunResource;
use Filament\Resources\Pages\CreateRecord;
class CreatePayrollRun extends CreateRecord
{
    protected static string $resource = PayrollRunResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = auth()->user()->company_id;
        $data['status']     = 'draft';
        $data['created_by'] = auth()->id();
        return $data;
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
