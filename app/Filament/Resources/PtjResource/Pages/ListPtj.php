<?php
namespace App\Filament\Resources\PtjResource\Pages;
use App\Filament\Resources\PtjResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;
class ListPtj extends ListRecords
{
    protected static string $resource = PtjResource::class;
    protected function getHeaderActions(): array { return [CreateAction::make()]; }
}
