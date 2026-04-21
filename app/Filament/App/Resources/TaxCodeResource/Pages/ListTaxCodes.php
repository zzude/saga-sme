<?php
namespace App\Filament\App\Resources\TaxCodeResource\Pages;
use App\Filament\App\Resources\TaxCodeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
class ListTaxCodes extends ListRecords
{
    protected static string $resource = TaxCodeResource::class;
    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}