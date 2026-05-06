<?php
// ListEmployees.php
namespace App\Filament\Resources\Payroll\Pages;
use App\Filament\Resources\Payroll\EmployeeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;
    protected function getHeaderActions(): array { return [CreateAction::make()]; }
}
