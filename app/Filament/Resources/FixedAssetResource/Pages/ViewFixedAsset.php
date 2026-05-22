<?php
namespace App\Filament\Resources\FixedAssetResource\Pages;
use App\Filament\Resources\FixedAssetResource;
use App\Services\FixedAssetService;
use App\Models\AccountingPeriod;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class ViewFixedAsset extends ViewRecord
{
    protected static string $resource = FixedAssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn () => $this->record->status === 'active'),

            Action::make('depreciate')
                ->label('Rekod Susut Nilai')
                ->icon('heroicon-o-calculator')
                ->color('warning')
                ->visible(fn () => $this->record->status === 'active' && !$this->record->isFullyDepreciated())
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
                    try {
                        $dep = app(FixedAssetService::class)->depreciate(
                            $this->record,
                            $data['depreciation_date'],
                            $data['period_id'] ?? null
                        );
                        Notification::make()
                            ->title('Susut Nilai Direkod')
                            ->body('Amaun: RM ' . number_format($dep->amount, 2) . ' | Nilai Buku: RM ' . number_format($dep->book_value_after, 2))
                            ->success()->send();
                        $this->refreshFormData(['current_book_value', 'accumulated_depreciation', 'status']);
                    } catch (\Exception $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),

            Action::make('dispose')
                ->label('Lupuskan Aset')
                ->icon('heroicon-o-archive-box-x-mark')
                ->color('danger')
                ->visible(fn () => $this->record->status === 'active')
                ->requiresConfirmation()
                ->modalHeading('Lupuskan Aset')
                ->modalDescription('Aset akan ditanda sebagai Dilupuskan dan jurnal pelupusan akan dibuat.')
                ->form([
                    DatePicker::make('disposal_date')
                        ->label('Tarikh Pelupusan')
                        ->required()
                        ->default(now()->format('Y-m-d')),

                    TextInput::make('disposal_proceeds')
                        ->label('Hasil Jualan / Pelupusan (RM)')
                        ->numeric()
                        ->default(0)
                        ->prefix('RM')
                        ->helperText('Isi 0 jika tiada hasil jualan.'),
                ])
                ->action(function (array $data) {
                    try {
                        app(FixedAssetService::class)->dispose(
                            $this->record,
                            $data['disposal_date'],
                            (float) $data['disposal_proceeds']
                        );
                        Notification::make()->title('Aset Dilupuskan')->success()->send();
                        $this->refreshFormData(['status', 'disposed_at', 'disposal_proceeds']);
                    } catch (\Exception $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }
}
