<?php
namespace App\Filament\Resources\Payroll\Pages;
use App\Filament\Resources\Payroll\EmployeeResource;
use Filament\Resources\Pages\EditRecord;
class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();
        return $data;
    }
}
