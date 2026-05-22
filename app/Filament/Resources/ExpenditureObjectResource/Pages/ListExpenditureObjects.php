<?php
namespace App\Filament\Resources\ExpenditureObjectResource\Pages;
use App\Filament\Resources\ExpenditureObjectResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;
class ListExpenditureObjects extends ListRecords
{
    protected static string $resource = ExpenditureObjectResource::class;
    protected function getHeaderActions(): array { return [CreateAction::make()]; }
}
