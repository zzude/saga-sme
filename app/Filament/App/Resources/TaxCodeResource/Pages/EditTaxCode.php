<?php
namespace App\Filament\App\Resources\TaxCodeResource\Pages;
use App\Filament\App\Resources\TaxCodeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
class EditTaxCode extends EditRecord
{
    protected static string $resource = TaxCodeResource::class;
    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}