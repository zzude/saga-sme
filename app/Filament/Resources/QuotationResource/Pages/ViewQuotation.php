<?php

namespace App\Filament\Resources\QuotationResource\Pages;

use App\Filament\Resources\QuotationResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class ViewQuotation extends ViewRecord
{
    protected static string $resource = QuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn () => $this->record->status === 'draft'),

            Action::make('markSent')
                ->label('Hantar ke Pelanggan')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->visible(fn () => $this->record->status === 'draft')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update([
                        'status'  => 'sent',
                        'sent_by' => auth()->id(),
                        'sent_at' => now(),
                    ]);
                    Notification::make()->title('Sebut Harga dihantar.')->success()->send();
                    $this->refreshFormData(['status']);
                }),

            Action::make('markAccepted')
                ->label('Pelanggan Terima')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->status === 'sent')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update([
                        'status'      => 'accepted',
                        'accepted_by' => auth()->id(),
                        'accepted_at' => now(),
                    ]);
                    Notification::make()->title('Diterima.')->success()->send();
                    $this->refreshFormData(['status']);
                }),

            Action::make('markRejected')
                ->label('Pelanggan Tolak')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $this->record->status === 'sent')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['status' => 'rejected']);
                    Notification::make()->title('Ditolak.')->warning()->send();
                    $this->refreshFormData(['status']);
                }),

            Action::make('createRevision')
                ->label('Buat Semakan')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn () => in_array($this->record->status, ['sent', 'rejected']))
                ->requiresConfirmation()
                ->action(function () {
                    $newRevision = $this->record->createRevision();
                    Notification::make()
                        ->title("Semakan {$newRevision->revision} dibuat: {$newRevision->quotation_number}")
                        ->success()->send();
                    $this->redirect(QuotationResource::getUrl('edit', ['record' => $newRevision]));
                }),

            Action::make('convertToInvoice')
                ->label('Tukar ke Invois')
                ->icon('heroicon-o-document-arrow-right')
                ->color('success')
                ->visible(fn () => $this->record->status === 'accepted')
                ->requiresConfirmation()
                ->action(function () {
                    try {
                        $invoice = $this->record->convertToInvoice();
                        Notification::make()->title("Invois {$invoice->invoice_number} berjaya!")->success()->send();
                        $this->redirect(
                            \App\Filament\Resources\Invoices\InvoiceResource::getUrl('view', ['record' => $invoice])
                        );
                    } catch (\Exception $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),

            Action::make('printPdf')
                ->label('Cetak PDF')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn () => route('quotation.pdf', $this->record))
                ->openUrlInNewTab(),
        ];
    }
}
