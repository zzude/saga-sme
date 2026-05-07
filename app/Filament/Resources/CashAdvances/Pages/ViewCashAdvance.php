<?php

namespace App\Filament\Resources\CashAdvances\Pages;

use App\Filament\Resources\CashAdvances\CashAdvanceResource;
use App\Models\Account;
use App\Services\CashAdvanceService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewCashAdvance extends ViewRecord
{
    protected static string $resource = CashAdvanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Approve')
                ->color('info')
                ->icon('heroicon-o-check-circle')
                ->visible(fn() => $this->record->status === 'draft')
                ->form([
                    TextInput::make('amount_approved')
                        ->label('Jumlah Diluluskan (RM)')
                        ->numeric()
                        ->prefix('RM')
                        ->default(fn() => $this->record->amount_requested)
                        ->required(),
                ])
                ->action(function (array $data) {
                    app(CashAdvanceService::class)->approve(
                        $this->record,
                        (float) $data['amount_approved']
                    );
                    Notification::make()->title('Cash Advance diluluskan!')->success()->send();
                    $this->refreshFormData(['status', 'amount_approved', 'approved_date']);
                }),

            Action::make('disburse')
                ->label('Disburse')
                ->color('warning')
                ->icon('heroicon-o-banknotes')
                ->visible(fn() => $this->record->status === 'approved')
                ->form([
                    Select::make('bank_account_id')
                        ->label('Bayar Dari Akaun')
                        ->options(function () {
                            return Account::where('company_id', $this->record->company_id)
                                ->whereIn('code', ['1110', '1120'])
                                ->pluck('name', 'id');
                        })
                        ->required(),
                ])
                ->action(function (array $data) {
                    app(CashAdvanceService::class)->disburse(
                        $this->record,
                        (int) $data['bank_account_id']
                    );
                    Notification::make()->title('Cash Advance telah dibayar!')->success()->send();
                    $this->refreshFormData(['status', 'disbursed_date', 'journal_id']);
                }),

            Action::make('settle')
                ->label('Settle')
                ->color('success')
                ->icon('heroicon-o-clipboard-document-check')
                ->visible(fn() => $this->record->status === 'disbursed')
                ->form([
                    Select::make('settlement_type')
                        ->label('Jenis Settlement')
                        ->options([
                            'expense_claim' => 'Expense Claim',
                            'cash_return'   => 'Cash Return',
                            'payroll_deduct'=> 'Poton Gaji',
                        ])
                        ->required()
                        ->live(),

                    Select::make('expense_account_id')
                        ->label('Akaun')
                        ->options(function () {
                            return Account::where('company_id', $this->record->company_id)
                                ->where('type', 'expense')
                                ->orWhere(function($q) {
                                    $q->where('company_id', $this->record->company_id)
                                      ->whereIn('code', ['1110', '1120']);
                                })
                                ->orderBy('code')
                                ->pluck('name', 'id');
                        })
                        ->required(),

                    TextInput::make('amount')
                        ->label('Jumlah (RM)')
                        ->numeric()
                        ->prefix('RM')
                        ->default(fn() => $this->record->amount_approved - $this->record->amount_settled)
                        ->required(),

                    TextInput::make('reference')
                        ->label('No. Rujukan')
                        ->placeholder('No. resit / claim'),
                ])
                ->action(function (array $data) {
                    app(CashAdvanceService::class)->settle(
                        $this->record,
                        (float) $data['amount'],
                        $data['settlement_type'],
                        (int) $data['expense_account_id'],
                        $data['reference'] ?? null
                    );
                    Notification::make()->title('Settlement berjaya!')->success()->send();
                    $this->refreshFormData(['status', 'amount_settled']);
                }),

            Action::make('cancel')
                ->label('Batal')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->visible(fn() => in_array($this->record->status, ['draft', 'approved']))
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['status' => 'cancelled']);
                    Notification::make()->title('Cash Advance dibatalkan.')->warning()->send();
                    $this->refreshFormData(['status']);
                }),
        ];
    }
}
