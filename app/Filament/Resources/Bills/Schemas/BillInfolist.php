<?php
namespace App\Filament\Resources\Bills\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BillInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Bill Details')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('company.name')
                            ->label('Company'),
                        TextEntry::make('vendor.name')
                            ->label('Vendor'),
                        TextEntry::make('period.name')
                            ->label('Period'),
                        TextEntry::make('bill_no')
                            ->label('Bill No'),
                        TextEntry::make('reference_no')
                            ->label('Vendor Invoice No')
                            ->placeholder('-'),
                        TextEntry::make('date')
                            ->label('Bill Date')
                            ->date(),
                        TextEntry::make('due_date')
                            ->label('Due Date')
                            ->date(),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'draft'     => 'gray',
                                'submitted' => 'info',
                                'approved'  => 'success',
                                'partial'   => 'warning',
                                'paid'      => 'success',
                                'overdue'   => 'danger',
                                'void'      => 'danger',
                                default     => 'gray',
                            }),
                        TextEntry::make('notes')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),

                Section::make('Currency & Rate')
                    ->columns(4)
                    ->schema([
                        TextEntry::make('currency_code')
                            ->label('Currency')
                            ->badge()
                            ->color(fn (string $state): string => $state === 'MYR' ? 'gray' : 'info'),

                        TextEntry::make('exchange_rate')
                            ->label('Exchange Rate')
                            ->numeric(decimalPlaces: 6)
                            ->placeholder('1.000000'),

                        TextEntry::make('exchange_rate_date')
                            ->label('Rate Date')
                            ->date()
                            ->placeholder('-'),

                        TextEntry::make('rate_source')
                            ->label('Rate Source')
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'OVERRIDE' => 'warning',
                                'MANUAL'   => 'warning',
                                default    => 'success',
                            })
                            ->placeholder('-'),
                    ]),

                Section::make('Amounts')
                    ->columns(3)
                    ->schema([
                        // Foreign currency row
                        TextEntry::make('subtotal')
                            ->label(fn ($record) => 'Subtotal (' . ($record->currency_code ?? 'MYR') . ')')
                            ->numeric(decimalPlaces: 2),

                        TextEntry::make('tax_amount')
                            ->label(fn ($record) => 'Tax (' . ($record->currency_code ?? 'MYR') . ')')
                            ->numeric(decimalPlaces: 2),

                        TextEntry::make('total')
                            ->label(fn ($record) => 'Total (' . ($record->currency_code ?? 'MYR') . ')')
                            ->numeric(decimalPlaces: 2)
                            ->weight('bold'),

                        // MYR base row — non-MYR only
                        TextEntry::make('base_subtotal')
                            ->label('Subtotal (MYR)')
                            ->numeric(decimalPlaces: 2)
                            ->color('success')
                            ->visible(fn ($record) => ($record->currency_code ?? 'MYR') !== 'MYR'),

                        TextEntry::make('base_tax')
                            ->label('Tax (MYR)')
                            ->numeric(decimalPlaces: 2)
                            ->color('success')
                            ->visible(fn ($record) => ($record->currency_code ?? 'MYR') !== 'MYR'),

                        TextEntry::make('base_total')
                            ->label('Total (MYR) — GL Amount')
                            ->numeric(decimalPlaces: 2)
                            ->color('success')
                            ->weight('bold')
                            ->visible(fn ($record) => ($record->currency_code ?? 'MYR') !== 'MYR'),

                        // Payment tracking
                        TextEntry::make('paid_amount')
                            ->label('Paid Amount (MYR)')
                            ->numeric(decimalPlaces: 2),

                        TextEntry::make('balance_due')
                            ->label('Balance Due (MYR)')
                            ->numeric(decimalPlaces: 2)
                            ->color(fn ($record) => (float) $record->balance_due > 0 ? 'danger' : 'success'),
                    ]),

                Section::make('Posting & Approval')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('journal.reference_no')
                            ->label('Journal Ref')
                            ->placeholder('-'),

                        TextEntry::make('posted_at')
                            ->label('Posted At')
                            ->dateTime()
                            ->placeholder('-'),

                        TextEntry::make('approvedBy.name')
                            ->label('Approved By')
                            ->placeholder('-'),

                        TextEntry::make('approved_at')
                            ->label('Approved At')
                            ->dateTime()
                            ->placeholder('-'),

                        TextEntry::make('voidedBy.name')
                            ->label('Voided By')
                            ->placeholder('-')
                            ->visible(fn ($record) => $record->status === 'void'),

                        TextEntry::make('voided_at')
                            ->label('Voided At')
                            ->dateTime()
                            ->placeholder('-')
                            ->visible(fn ($record) => $record->status === 'void'),

                        TextEntry::make('void_reason')
                            ->label('Void Reason')
                            ->placeholder('-')
                            ->columnSpanFull()
                            ->visible(fn ($record) => $record->status === 'void'),

                        TextEntry::make('createdBy.name')
                            ->label('Created By'),

                        TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime(),
                    ]),

            ]);
    }
}
