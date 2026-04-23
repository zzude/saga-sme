<?php

namespace App\Filament\Resources\Invoices\Tables;

use App\Enums\EInvoiceStatus;
use App\Jobs\CheckInvoiceStatusJob;
use App\Jobs\SubmitInvoiceJob;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_no')
                    ->label('Invoice No')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable(),
                TextColumn::make('date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft'   => 'gray',
                        'sent'    => 'info',
                        'partial' => 'warning',
                        'paid'    => 'success',
                        'overdue' => 'danger',
                        'void'    => 'danger',
                        default   => 'gray',
                    }),

                // e-Invoice Status Badge
                TextColumn::make('einvoice_status')
                    ->label('e-Invoice')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'draft'      => 'gray',
                        'submitted'  => 'warning',
                        'processing' => 'info',
                        'valid'      => 'success',
                        'rejected'   => 'danger',
                        'cancelled'  => 'gray',
                        default      => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'draft'      => 'Draft',
                        'submitted'  => 'Submitted',
                        'processing' => 'Processing',
                        'valid'      => 'Valid ✓',
                        'rejected'   => 'Rejected ✗',
                        'cancelled'  => 'Cancelled',
                        default      => '-',
                    })
                    ->toggleable(),

                // e-Invoice UUID
                TextColumn::make('einvoice_uuid')
                    ->label('UUID')
                    ->limit(20)
                    ->tooltip(fn ($record) => $record?->einvoice_uuid)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('total')
                    ->label('Total (MYR)')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('paid_amount')
                    ->label('Paid (MYR)')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('balance_due')
                    ->label('Balance (MYR)')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('posted_at')
                    ->label('Posted At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),

                // Check Status Action
                Action::make('check_einvoice_status')
                    ->label('Check Status')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->visible(fn ($record) => in_array(
                        $record->einvoice_status,
                        ['submitted', 'processing']
                    ))
                    ->action(function ($record) {
                        CheckInvoiceStatusJob::dispatch($record);
                        Notification::make()
                            ->title('Status check queued!')
                            ->body('e-Invoice status will be updated shortly.')
                            ->success()
                            ->send();
                    }),

                // Resubmit Action
                Action::make('resubmit_einvoice')
                    ->label('Resubmit')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->visible(fn ($record) => $record->einvoice_status === 'rejected')
                    ->requiresConfirmation()
                    ->modalHeading('Resubmit e-Invoice?')
                    ->modalDescription('This will resubmit the invoice to LHDN MyInvois.')
                    ->action(function ($record) {
                        $record->update(['einvoice_status' => 'draft']);
                        SubmitInvoiceJob::dispatch($record);
                        Notification::make()
                            ->title('Resubmission queued!')
                            ->body('Invoice will be resubmitted to LHDN shortly.')
                            ->warning()
                            ->send();
                    }),

                // Cancel e-Invoice Action
                Action::make('cancel_einvoice')
                    ->label('Cancel e-Invoice')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->einvoice_status === 'valid')
                    ->requiresConfirmation()
                    ->modalHeading('Cancel e-Invoice?')
                    ->modalDescription('This will cancel the e-Invoice at LHDN. This action cannot be undone.')
                    ->action(function ($record) {
                        $record->update(['einvoice_status' => 'cancelled']);
                        Notification::make()
                            ->title('e-Invoice cancelled')
                            ->danger()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc');
    }
}