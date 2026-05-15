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

class EditVirement extends EditRecord
{
    protected static string $resource = VirementResource::class;
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
}

class ViewVirement extends ViewRecord
{
    protected static string $resource = VirementResource::class;
    protected function getHeaderActions(): array
    {
        return [Actions\EditAction::make()->visible(fn() => $this->record->isEditable())];
    }
}
