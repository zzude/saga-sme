<?php

namespace App\Filament\Resources\CashAdvances\Schemas;

use App\Models\Account;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Set;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CashAdvanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Maklumat Permohonan')
                    ->columns(2)
                    ->components([
                        TextInput::make('advance_no')
                            ->label('No. Advance')
                            ->default(fn() => 'CA-' . date('Y') . '-' . str_pad(random_int(1, 999), 3, '0', STR_PAD_LEFT))
                            ->required()
                            ->columnSpan(1),

                        Select::make('employee_id')
                            ->label('Pekerja')
                            ->relationship('employee', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(1),

                        TextInput::make('purpose')
                            ->label('Tujuan')
                            ->required()
                            ->columnSpanFull(),

                        TextInput::make('amount_requested')
                            ->label('Jumlah Dipohon (RM)')
                            ->numeric()
                            ->prefix('RM')
                            ->required()
                            ->columnSpan(1),

                        DatePicker::make('applied_date')
                            ->label('Tarikh Permohonan')
                            ->default(now())
                            ->required()
                            ->columnSpan(1),

                        DatePicker::make('due_date')
                            ->label('Tarikh Perlu Settle')
                            ->columnSpan(1),

                        Textarea::make('notes')
                            ->label('Catatan')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
