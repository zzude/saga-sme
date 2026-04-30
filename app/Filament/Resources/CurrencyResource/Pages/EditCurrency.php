<?php

// app/Filament/Resources/CurrencyResource/Pages/EditCurrency.php
 
namespace App\Filament\Resources\CurrencyResource\Pages;
 
use App\Filament\Resources\CurrencyResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
 
class EditCurrency extends EditRecord
{
    protected static string $resource = CurrencyResource::class;
 
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(fn ($record) => $record->code === 'MYR'), // never delete base currency
        ];
    }
}