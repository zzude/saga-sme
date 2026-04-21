<?php

namespace App\Filament\Resources\Bills\Pages;

use App\Filament\Resources\Bills\BillResource;
use App\Services\BillService;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewBill extends ViewRecord
{
    protected static string $resource = BillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('post')
                ->label('Post to GL')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => in_array($this->record->status, ['draft', 'submitted']))
                ->requiresConfirmation()
                ->modalHeading('Post Bill to General Ledger')
                ->modalDescription('This will create a journal entry for this bill. Continue?')
                ->action(function () {
                    try {
                        $service = new BillService();
                        $service->post($this->record);
                        Notification::make()->title('Bill posted successfully!')->success()->send();
                        $this->refreshFormData(['status', 'posted_at']);
                    } catch (\Exception $e) {
                        Notification::make()->title('Error: ' . $e->getMessage())->danger()->send();
                    }
                }),

            Action::make('recordPayment')
                ->label('Record Payment')
                ->icon('heroicon-o-banknotes')
                ->color('info')
                ->visible(fn () => in_array($this->record->status, ['approved', 'partial']))
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
                            ->pluck('name', 'id'))
                        ->required(),
                    \Filament\Forms\Components\TextInput::make('amount')
                        ->label('Amount (MYR)')
                        ->numeric()
                        ->default(fn () => $this->record->balance_due)
                        ->required(),
                    \Filament\Forms\Components\Select::make('payment_method')
                        ->options([
                            'cash'     => 'Cash',
                            'transfer' => 'Bank Transfer',
                            'cheque'   => 'Cheque',
                            'online'   => 'Online',
                        ])
                        ->default('transfer')
                        ->required(),
                    \Filament\Forms\Components\TextInput::make('reference_no')->nullable(),
                    \Filament\Forms\Components\TextInput::make('remarks')->nullable(),
                ])
                ->action(function (array $data) {
                    try {
                        $service = new BillService();
                        $service->recordPayment($this->record, $data);
                        Notification::make()->title('Payment recorded!')->success()->send();
                        $this->refreshFormData(['status', 'paid_amount', 'balance_due']);
                    } catch (\Exception $e) {
                        Notification::make()->title('Error: ' . $e->getMessage())->danger()->send();
                    }
                }),

            Action::make('void')
                ->label('Void')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => in_array($this->record->status, ['approved', 'partial']))
                ->requiresConfirmation()
                ->form([
                    \Filament\Forms\Components\Textarea::make('void_reason')
                        ->label('Reason')
                        ->required(),
                ])
                ->action(function (array $data) {
                    try {
                        $service = new BillService();
                        $service->void($this->record, $data['void_reason']);
                        Notification::make()->title('Bill voided.')->warning()->send();
                        $this->refreshFormData(['status']);
                    } catch (\Exception $e) {
                        Notification::make()->title('Error: ' . $e->getMessage())->danger()->send();
                    }
                }),

            EditAction::make(),
        ];
    }
}