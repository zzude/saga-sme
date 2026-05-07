<?php

namespace App\Filament\Resources\LeaveTypes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LeaveTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Cuti')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('days_per_year')
                    ->label('Hari/Tahun')
                    ->sortable(),

                IconColumn::make('is_paid')
                    ->label('Berbayar')
                    ->boolean(),

                IconColumn::make('is_carry_forward')
                    ->label('Carry Forward')
                    ->boolean(),

                TextColumn::make('max_carry_forward_days')
                    ->label('Max CF (Hari)'),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
