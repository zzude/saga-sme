<?php
namespace App\Filament\Resources\Invoices\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InvoiceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Invoice Details')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('company.name')
                            ->label('Company'),
                        TextEntry::make('customer.name')
                            ->label('Customer'),
                        TextEntry::make('period.name')
                            ->label('Period'),
                        TextEntry::make('invoice_no')
                            ->label('Invoice No'),
                        TextEntry::make('date')
                            ->label('Date')
                            ->date(),
                        TextEntry::make('due_date')
                            ->label('Due Date')
                            ->date(),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'draft'   => 'gray',
                                'sent'    => 'info',
                                'partial' => 'warning',
                                'paid'    => 'success',
                                'overdue' => 'danger',
                                'void'    => 'danger',
                                default   => 'gray',
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
                        // Foreign currency row — always visible
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

                        // MYR base row — shown for non-MYR invoices
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

                Section::make('Posting Info')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('posted_at')
                            ->label('Posted At')
                            ->dateTime()
                            ->placeholder('-'),

                        TextEntry::make('createdBy.name')
                            ->label('Created By'),

                        TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime(),
                    ]),

            ]);
    }
}
