<?php

namespace App\Filament\Resources\Invoices\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class InvoiceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                TextEntry::make('currency_code')
                    ->label('Currency'),
                TextEntry::make('subtotal')
                    ->label('Subtotal (MYR)')
                    ->numeric(decimalPlaces: 2),
                TextEntry::make('tax_amount')
                    ->label('Tax Amount (MYR)')
                    ->numeric(decimalPlaces: 2),
                TextEntry::make('total')
                    ->label('Total (MYR)')
                    ->numeric(decimalPlaces: 2),
                TextEntry::make('paid_amount')
                    ->label('Paid Amount (MYR)')
                    ->numeric(decimalPlaces: 2),
                TextEntry::make('balance_due')
                    ->label('Balance Due (MYR)')
                    ->numeric(decimalPlaces: 2),
                TextEntry::make('notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('posted_at')
                    ->label('Posted At')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('createdBy.name')
                    ->label('Created By'),
                TextEntry::make('created_at')
                    ->label('Created At')
                    ->dateTime(),
            ]);
    }
}