<?php

namespace App\Filament\Resources\LeaveApplications\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LeaveApplicationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Maklumat Permohonan')
                    ->columns(2)
                    ->components([
                        TextEntry::make('application_no')
                            ->label('No. Permohonan')
                            ->weight('bold'),

                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn(string $state): string => match($state) {
                                'draft'     => 'gray',
                                'approved'  => 'success',
                                'rejected'  => 'danger',
                                'cancelled' => 'warning',
                                default     => 'gray',
                            }),

                        TextEntry::make('employee.name')
                            ->label('Pekerja'),

                        TextEntry::make('leaveType.name')
                            ->label('Jenis Cuti'),

                        TextEntry::make('start_date')
                            ->label('Tarikh Mula')
                            ->date('d/m/Y'),

                        TextEntry::make('end_date')
                            ->label('Tarikh Tamat')
                            ->date('d/m/Y'),

                        TextEntry::make('total_days')
                            ->label('Jumlah Hari'),

                        TextEntry::make('reason')
                            ->label('Sebab')
                            ->columnSpanFull()
                            ->placeholder('-'),
                    ]),

                Section::make('Kelulusan')
                    ->columns(2)
                    ->components([
                        TextEntry::make('approvedBy.name')
                            ->label('Diluluskan Oleh')
                            ->placeholder('-'),

                        TextEntry::make('approved_date')
                            ->label('Tarikh Kelulusan')
                            ->date('d/m/Y')
                            ->placeholder('-'),

                        TextEntry::make('notes')
                            ->label('Catatan')
                            ->columnSpanFull()
                            ->placeholder('-'),
                    ]),
            ]);
    }
}
