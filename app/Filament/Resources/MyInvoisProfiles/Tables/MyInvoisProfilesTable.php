<?php

namespace App\Filament\Resources\MyInvoisProfiles\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MyInvoisProfilesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make("environment")
                    ->badge()
                    ->color(fn($state) => $state === "production" ? "danger" : "warning"),
                TextColumn::make("mode")->badge()->color("info"),
                TextColumn::make("tin")->label("TIN")->default("-"),
                TextColumn::make("client_id")->label("Client ID")->default("-"),
                IconColumn::make("is_active")->label("Active")->boolean(),
                TextColumn::make("updated_at")->dateTime("d M Y")->sortable(),
            ])
            ->filters([])
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
