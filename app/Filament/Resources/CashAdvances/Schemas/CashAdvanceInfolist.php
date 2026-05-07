<?php

namespace App\Filament\Resources\CashAdvances\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CashAdvanceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Maklumat Permohonan')
                    ->columns(2)
                    ->components([
                        TextEntry::make('advance_no')
                            ->label('No. Advance')
                            ->weight('bold'),

                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn(string $state): string => match($state) {
                                'draft'     => 'gray',
                                'approved'  => 'info',
                                'disbursed' => 'warning',
                                'settled'   => 'success',
                                'cancelled' => 'danger',
                                default     => 'gray',
                            }),

                        TextEntry::make('employee.name')
                            ->label('Pekerja'),

                        TextEntry::make('company.name')
                            ->label('Syarikat'),

                        TextEntry::make('purpose')
                            ->label('Tujuan')
                            ->columnSpanFull(),

                        TextEntry::make('notes')
                            ->label('Catatan')
                            ->columnSpanFull()
                            ->placeholder('-'),
                    ]),

                Section::make('Maklumat Kewangan')
                    ->columns(3)
                    ->components([
                        TextEntry::make('amount_requested')
                            ->label('Jumlah Dipohon')
                            ->money('MYR'),

                        TextEntry::make('amount_approved')
                            ->label('Jumlah Diluluskan')
                            ->money('MYR')
                            ->placeholder('-'),

                        TextEntry::make('amount_settled')
                            ->label('Jumlah Settled')
                            ->money('MYR'),
                    ]),

                Section::make('Tarikh & Kelulusan')
                    ->columns(2)
                    ->components([
                        TextEntry::make('applied_date')
                            ->label('Tarikh Permohonan')
                            ->date('d/m/Y'),

                        TextEntry::make('due_date')
                            ->label('Tarikh Due')
                            ->date('d/m/Y')
                            ->placeholder('-'),

                        TextEntry::make('approved_date')
                            ->label('Tarikh Diluluskan')
                            ->date('d/m/Y')
                            ->placeholder('-'),

                        TextEntry::make('disbursed_date')
                            ->label('Tarikh Disbursed')
                            ->date('d/m/Y')
                            ->placeholder('-'),

                        TextEntry::make('approvedBy.name')
                            ->label('Diluluskan Oleh')
                            ->placeholder('-'),

                        TextEntry::make('disbursedBy.name')
                            ->label('Dibayar Oleh')
                            ->placeholder('-'),
                    ]),
            ]);
    }
}
