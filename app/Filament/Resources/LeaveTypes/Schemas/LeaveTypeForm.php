<?php

namespace App\Filament\Resources\LeaveTypes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LeaveTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Maklumat Jenis Cuti')
                    ->columns(2)
                    ->components([
                        TextInput::make('name')
                            ->label('Nama Cuti')
                            ->required()
                            ->columnSpanFull(),

                        TextInput::make('days_per_year')
                            ->label('Hari Per Tahun')
                            ->numeric()
                            ->default(0)
                            ->required(),

                        TextInput::make('max_carry_forward_days')
                            ->label('Max Carry Forward (Hari)')
                            ->numeric()
                            ->default(0),

                        Toggle::make('is_paid')
                            ->label('Cuti Berbayar')
                            ->default(true),

                        Toggle::make('is_carry_forward')
                            ->label('Boleh Carry Forward')
                            ->default(false),

                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ]),
            ]);
    }
}
