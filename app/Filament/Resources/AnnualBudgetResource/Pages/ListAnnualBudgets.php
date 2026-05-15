<?php
// ─── ListAnnualBudgets.php ───────────────────────────────────────
namespace App\Filament\Resources\AnnualBudgetResource\Pages;

use App\Filament\Resources\AnnualBudgetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAnnualBudgets extends ListRecords
{
    protected static string $resource = AnnualBudgetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('+ Bajet Baru'),
        ];
    }
}
