<?php
namespace App\Filament\Resources\ItemResource\Pages;
use App\Filament\Resources\ItemResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\DeleteAction;

class EditItem extends EditRecord
{
    protected static string $resource = ItemResource::class;
    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}