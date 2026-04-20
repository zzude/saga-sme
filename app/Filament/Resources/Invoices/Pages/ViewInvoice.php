<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Services\InvoiceService;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('post')
                ->label('Post to GL')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => in_array($this->record->status, ['draft', 'sent']))
                ->requiresConfirmation()
                ->modalHeading('Post Invoice to General Ledger')
                ->modalDescription('This will create a journal entry for this invoice. Continue?')
                ->action(function () {
                    try {
                        $service = new InvoiceService();
                        $service->post($this->record);

                        Notification::make()
                            ->title('Invoice posted successfully!')
                            ->success()
                            ->send();

                        $this->refreshFormData([
                            'status',
                            'posted_at',
                        ]);

                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('void')
                ->label('Void')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => in_array($this->record->status, ['sent', 'partial']))
                ->requiresConfirmation()
                ->modalHeading('Void Invoice')
                ->modalDescription('This will void the invoice and reverse the journal entry.')
                ->form([
                    \Filament\Forms\Components\Textarea::make('void_reason')
                        ->label('Reason')
                        ->required(),
                ])
                ->action(function (array $data) {
                    try {
                        $service = new InvoiceService();
                        $service->void($this->record, $data['void_reason']);

                        Notification::make()
                            ->title('Invoice voided.')
                            ->warning()
                            ->send();

                        $this->refreshFormData(['status']);

                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            EditAction::make(),
        ];
    }
}