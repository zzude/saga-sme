<?php
namespace App\Filament\Resources\Payroll\Pages;
use App\Filament\Resources\Payroll\EmployeeResource;
use Filament\Resources\Pages\CreateRecord;
class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = auth()->user()->company_id;
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();
        return $data;
    }
}
