<?php

namespace App\Filament\Resources\LeaveApplications\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LeaveApplicationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Maklumat Permohonan Cuti')
                    ->columns(2)
                    ->components([
                        TextInput::make('application_no')
                            ->label('No. Permohonan')
                            ->default(fn() => 'LV-' . date('Y') . '-' . str_pad(random_int(1, 999), 3, '0', STR_PAD_LEFT))
                            ->required()
                            ->columnSpan(1),

                        Select::make('employee_id')
                            ->label('Pekerja')
                            ->relationship('employee', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(1),

                        Select::make('leave_type_id')
                            ->label('Jenis Cuti')
                            ->relationship('leaveType', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpanFull(),

                        DatePicker::make('start_date')
                            ->label('Tarikh Mula')
                            ->required()
                            ->live()
                            ->columnSpan(1),

                        DatePicker::make('end_date')
                            ->label('Tarikh Tamat')
                            ->required()
                            ->live()
                            ->columnSpan(1),

                        TextInput::make('total_days')
                            ->label('Jumlah Hari')
                            ->numeric()
                            ->required()
                            ->columnSpan(1),

                        Textarea::make('reason')
                            ->label('Sebab')
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('notes')
                            ->label('Catatan')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
