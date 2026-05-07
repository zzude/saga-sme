<?php

namespace App\Filament\Resources\CashAdvances\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CashAdvancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('advance_no')
                    ->label('No. Advance')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('employee.name')
                    ->label('Pekerja')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('purpose')
                    ->label('Tujuan')
                    ->limit(30)
                    ->searchable(),

                TextColumn::make('amount_requested')
                    ->label('Jumlah Dipohon')
                    ->money('MYR')
                    ->sortable(),

                TextColumn::make('amount_approved')
                    ->label('Jumlah Diluluskan')
                    ->money('MYR')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match($state) {
                        'draft'      => 'gray',
                        'approved'   => 'info',
                        'disbursed'  => 'warning',
                        'settled'    => 'success',
                        'cancelled'  => 'danger',
                        default      => 'gray',
                    }),

                TextColumn::make('applied_date')
                    ->label('Tarikh Pohon')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft'     => 'Draft',
                        'approved'  => 'Approved',
                        'disbursed' => 'Disbursed',
                        'settled'   => 'Settled',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
