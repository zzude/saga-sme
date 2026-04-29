<?php

namespace App\Filament\Resources\Customers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->searchable(),
                TextColumn::make('customer_code')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('city')
                    ->searchable(),
                TextColumn::make('state')
                    ->searchable(),
                TextColumn::make('postcode')
                    ->searchable(),
                TextColumn::make('country')
                    ->searchable(),
                TextColumn::make('registration_no')
                    ->searchable(),
                TextColumn::make('tax_id')
                    ->searchable(),
                TextColumn::make('credit_term_days')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('credit_limit')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_individual')
                    ->label('B2C/Ind.')
                    ->boolean()
                    ->trueIcon('heroicon-o-user')
                    ->falseIcon('heroicon-o-building-office-2')
                    ->trueColor('warning')
                    ->falseColor('success')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_individual')
                    ->label('Customer Type')
                    ->placeholder('All customers')
                    ->trueLabel('Individual / B2C only')
                    ->falseLabel('Business / B2B only'),
            ])
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