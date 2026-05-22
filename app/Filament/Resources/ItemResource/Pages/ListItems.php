<?php
namespace App\Filament\Resources\ItemResource\Pages;
use App\Filament\Resources\ItemResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;

class ListItems extends ListRecords
{
    protected static string $resource = ItemResource::class;
    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}