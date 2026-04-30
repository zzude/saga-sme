<?php

// app/Filament/Resources/ExchangeRateResource/Pages/EditExchangeRate.php
 
namespace App\Filament\Resources\ExchangeRateResource\Pages;
 
use App\Filament\Resources\ExchangeRateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
 
class EditExchangeRate extends EditRecord
{
    protected static string $resource = ExchangeRateResource::class;
 
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['source']     = 'MANUAL';
        $data['fetched_at'] = now();
        $data['is_locked']  = true;
 
        return $data;
    }
 
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
 
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}