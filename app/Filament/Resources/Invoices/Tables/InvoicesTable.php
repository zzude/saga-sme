<?php

namespace App\Filament\Resources\Invoices\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc');
    }
}