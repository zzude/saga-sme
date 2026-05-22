<?php
namespace App\Filament\Resources\ItemResource\Pages;
use App\Filament\Resources\ItemResource;
use App\Models\Item;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;

class ViewItem extends ViewRecord
{
    protected static string $resource = ItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),

            Action::make('adjustStock')
                ->label('Laras Stok')
                ->icon('heroicon-o-arrows-up-down')
                ->color('warning')
                ->visible(fn () => $this->record->track_inventory)
                ->form([
                    Select::make('type')
                        ->label('Jenis Pelarasan')
                        ->options([
                            'in'         => 'Masuk (+)',
                            'out'        => 'Keluar (-)',
                            'adjustment' => 'Pelarasan Manual',
                        ])
                        ->required()
                        ->default('in'),

                    TextInput::make('quantity')
                        ->label('Kuantiti')
                        ->numeric()
                        ->required()
                        ->minValue(0.01),

                    TextInput::make('unit_cost')
                        ->label('Kos Seunit (RM)')
                        ->numeric()
                        ->default(0)
                        ->prefix('RM'),

                    Textarea::make('notes')
                        ->label('Catatan')
                        ->rows(2)
                        ->placeholder('Sebab pelarasan stok...'),
                ])
                ->action(function (array $data) {
                    try {
                        $this->record->adjustStock(
                            $data['type'],
                            (float) $data['quantity'],
                            (float) $data['unit_cost'],
                            null, null, null,
                            $data['notes'] ?? null
                        );
                        Notification::make()
                            ->title('Stok dikemaskini')
                            ->body('Stok semasa: ' . number_format($this->record->fresh()->current_stock, 2) . ' ' . $this->record->unit_of_measure)
                            ->success()->send();
                        $this->refreshFormData(['current_stock']);
                    } catch (\Exception $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }
}