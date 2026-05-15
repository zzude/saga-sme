<?php

namespace App\Filament\Resources\SupplementaryBudgetResource\Pages;

use App\Filament\Resources\SupplementaryBudgetResource;
use App\Models\SupplementaryBudget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ListSupplementaryBudgets extends ListRecords
{
    protected static string $resource = SupplementaryBudgetResource::class;
    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('+ Tambahan Baru')];
    }
}