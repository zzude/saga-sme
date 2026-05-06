<?php
namespace App\Filament\Resources\Payroll\Pages;
use App\Filament\Resources\Payroll\PayrollPeriodResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
class ListPayrollPeriods extends ListRecords
{
    protected static string $resource = PayrollPeriodResource::class;
    protected function getHeaderActions(): array { return [CreateAction::make()]; }
}
