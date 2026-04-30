<?php
// app/Filament/Resources/ExchangeRateResource/Pages/CreateExchangeRate.php
 
namespace App\Filament\Resources\ExchangeRateResource\Pages;
 
use App\Filament\Resources\ExchangeRateResource;
use App\Services\ExchangeRateService;
use Filament\Resources\Pages\CreateRecord;
 
class CreateExchangeRate extends CreateRecord
{
    protected static string $resource = ExchangeRateResource::class;
 
    // Override mutateFormDataBeforeCreate to set fields correctly
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['to_currency'] = 'MYR';
        $data['source']      = 'MANUAL';
        $data['fetched_at']  = now();
        $data['is_locked']   = true; // manual overrides are always locked
 
        return $data;
    }
 
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}