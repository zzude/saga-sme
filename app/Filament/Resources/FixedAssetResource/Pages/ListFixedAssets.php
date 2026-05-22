<?php
namespace App\Filament\Resources\FixedAssetResource\Pages;
use App\Filament\Resources\FixedAssetResource;
use App\Services\FixedAssetService;
use App\Models\AccountingPeriod;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;

class ListFixedAssets extends ListRecords
{
    protected static string $resource = FixedAssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('depreciateAll')
                ->label('Jalankan Susut Nilai Semua')
                ->icon('heroicon-o-calculator')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Jalankan Susut Nilai Bulanan')
                ->modalDescription('Sistem akan mengira dan merekod susut nilai untuk semua aset aktif.')
                ->form([
                    DatePicker::make('depreciation_date')
                        ->label('Tarikh Susut Nilai')
                        ->required()
                        ->default(now()->endOfMonth()->format('Y-m-d')),

                    Select::make('period_id')
                        ->label('Tempoh Perakaunan')
                        ->options(fn () => AccountingPeriod::where('company_id', auth()->user()->company_id)
                            ->where('status', 'open')
                            ->pluck('name', 'id'))
                        ->nullable()
                        ->placeholder('Pilih tempoh (optional)'),
                ])
                ->action(function (array $data) {
                    $service = app(FixedAssetService::class);
                    $results = $service->depreciateAll(
                        $data['depreciation_date'],
                        $data['period_id'] ?? null
                    );

                    $successCount = count($results['success']);
                    $skippedCount = count($results['skipped']);
                    $errorCount   = count($results['errors']);

                    Notification::make()
                        ->title("Susut Nilai Selesai")
                        ->body("Berjaya: {$successCount} aset | Dilangkau: {$skippedCount} | Ralat: {$errorCount}")
                        ->success()
                        ->send();
                }),

            CreateAction::make(),
        ];
    }
}
