<?php

namespace App\Filament\Resources\VirementResource\Pages;

use App\Filament\Resources\VirementResource;
use App\Models\Virement;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ListVirements extends ListRecords
{
    protected static string $resource = VirementResource::class;
    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('+ Virement Baru')];
    }
}