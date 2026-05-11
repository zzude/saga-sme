<?php

namespace App\Filament\Resources\Plans\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('price_monthly')
                    ->label('Price (MYR/mo)')
                    ->money('MYR')
                    ->sortable(),
                TextColumn::make('max_users')
                    ->label('Users')
                    ->formatStateUsing(fn ($state) => $state === -1 ? '∞' : $state),
                TextColumn::make('max_invoices_per_month')
                    ->label('Inv/Month')
                    ->formatStateUsing(fn ($state) => $state === -1 ? '∞' : $state),
                TextColumn::make('max_customers')
                    ->label('Customers')
                    ->formatStateUsing(fn ($state) => $state === -1 ? '∞' : $state),
                IconColumn::make('has_einvoice')
                    ->label('e-Invoice')
                    ->boolean(),
                IconColumn::make('has_multicurrency')
                    ->label('Multi-FX')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->defaultSort('sort_order')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
