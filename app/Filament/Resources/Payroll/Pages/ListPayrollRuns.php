<?php
namespace App\Filament\Resources\Payroll\Pages;
use App\Filament\Resources\Payroll\PayrollRunResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
class ListPayrollRuns extends ListRecords
{
    protected static string $resource = PayrollRunResource::class;
    protected function getHeaderActions(): array { return [CreateAction::make()]; }
}
