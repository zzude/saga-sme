<?php
// app/Filament/Resources/ExchangeRateResource/Pages/ListExchangeRates.php
 
namespace App\Filament\Resources\ExchangeRateResource\Pages;
 
use App\Filament\Resources\ExchangeRateResource;
use App\Services\ExchangeRateService;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
 
class ListExchangeRates extends ListRecords
{
    protected static string $resource = ExchangeRateResource::class;
 
    protected function getHeaderActions(): array
    {
        return [
            // "Fetch Today's Rates" button — calls ExchangeRateService directly
            Action::make('fetchToday')
                ->label("Fetch Today's Rates")
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading("Fetch Today's Exchange Rates")
                ->modalDescription('This will fetch live rates from Frankfurter API for all active currencies (USD, SGD). Existing locked/manual rates will not be overwritten.')
                ->modalSubmitActionLabel('Fetch Now')
                ->action(function (ExchangeRateService $service) {
                    $results = $service->fetchAndStoreAll(now());
 
                    $success = collect($results)->where('status', 'ok')->count();
                    $failed  = collect($results)->where('status', 'failed')->count();
                    $skipped = collect($results)->where('status', 'skipped')->count();
 
                    if ($failed > 0) {
                        Notification::make()
                            ->title('Partial failure')
                            ->body("{$success} fetched, {$failed} failed, {$skipped} skipped. Check logs for details.")
                            ->warning()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Exchange rates updated')
                            ->body("{$success} rate(s) fetched successfully." . ($skipped > 0 ? " {$skipped} skipped (locked)." : ''))
                            ->success()
                            ->send();
                    }
                }),
 
            CreateAction::make()
                ->label('Manual Override')
                ->icon('heroicon-o-pencil-square'),
        ];
    }
}