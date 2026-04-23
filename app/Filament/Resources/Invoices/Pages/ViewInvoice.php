<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Services\InvoiceService;
use App\Jobs\SubmitInvoiceJob;
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
                ->visible(fn () => in_array($this->record->status, ['draft']))
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

            Action::make('recordPayment')
                ->label('Record Payment')
                ->icon('heroicon-o-banknotes')
                ->color('info')
                ->visible(fn () => in_array($this->record->status, ['sent', 'partial']))
                ->form([
                    \Filament\Forms\Components\DatePicker::make('payment_date')
                        ->label('Payment Date')
                        ->default(now())
                        ->required(),

                    \Filament\Forms\Components\Select::make('bank_account_id')
                        ->label('Bank Account')
                        ->options(fn () => \App\Models\Account::where('company_id', $this->record->company_id)
                            ->where('level', 3)
                            ->where('type', 'asset')
                            ->whereNotNull('code')
                            ->pluck('name', 'id'))
                        ->required(),

                    \Filament\Forms\Components\TextInput::make('amount')
                        ->label('Amount (MYR)')
                        ->numeric()
                        ->default(fn () => $this->record->balance_due)
                        ->required(),

                    \Filament\Forms\Components\Select::make('payment_method')
                        ->label('Payment Method')
                        ->options([
                            'cash'     => 'Cash',
                            'transfer' => 'Bank Transfer',
                            'cheque'   => 'Cheque',
                            'online'   => 'Online',
                        ])
                        ->default('transfer')
                        ->required(),

                    \Filament\Forms\Components\TextInput::make('reference_no')
                        ->label('Reference No')
                        ->nullable(),

                    \Filament\Forms\Components\TextInput::make('remarks')
                        ->label('Remarks')
                        ->nullable(),
                ])
                ->action(function (array $data) {
                    try {
                        $service = new InvoiceService();
                        $service->recordPayment($this->record, $data);

                        Notification::make()
                            ->title('Payment recorded successfully!')
                            ->success()
                            ->send();

                        $this->refreshFormData([
                            'status',
                            'paid_amount',
                            'balance_due',
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
            Action::make('printPdf')
                ->label('Print PDF')
                ->icon('heroicon-o-printer')
                ->color('info')
                ->url(fn () => route('invoice.pdf', $this->record->id))
                ->openUrlInNewTab(),                

            Action::make("submitMyInvois")
                ->label(fn() => match($this->record->einvoice_status) {
                    'submitted'  => 'MyInvois: Submitted',
                    'processing' => 'MyInvois: Processing...',
                    'valid'      => 'MyInvois: Valid ✓',
                    'rejected'   => 'Resubmit to MyInvois',
                    'cancelled'  => 'MyInvois: Cancelled',
                    default      => 'Submit to MyInvois',
                })
                ->icon('heroicon-o-cloud-arrow-up')
                ->color(fn() => match($this->record->einvoice_status) {
                    'valid'      => 'success',
                    'rejected'   => 'danger',
                    'submitted',
                    'processing' => 'info',
                    default      => 'warning',
                })
                ->visible(fn() => in_array($this->record->status, ['sent', 'partial', 'paid'])
                    && !in_array($this->record->einvoice_status, ['valid', 'cancelled']))
                ->requiresConfirmation()
                ->modalHeading(fn() => $this->record->einvoice_status === 'rejected'
                    ? 'Resubmit to MyInvois (LHDN)?'
                    : 'Submit to MyInvois (LHDN)?')
                ->modalDescription(fn() => $this->record->einvoice_status === 'rejected'
                    ? 'Invoice akan dihantar semula ke LHDN MyInvois. Pastikan maklumat customer (TIN) sudah betul.'
                    : 'Invoice akan dihantar ke LHDN MyInvois untuk validasi e-Invoice.')
                ->action(function() {
                    // Reset status kalau rejected
                    if ($this->record->einvoice_status === 'rejected') {
                        \DB::table('invoices')
                            ->where('id', $this->record->id)
                            ->update(['einvoice_status' => 'draft']);
                    }

                    SubmitInvoiceJob::dispatch($this->record);

                    Notification::make()
                        ->title('Queued to MyInvois!')
                        ->body('Invoice sedang dihantar ke LHDN. Status akan update automatically.')
                        ->success()
                        ->send();
                }),

            EditAction::make(),
        ];
    }

}