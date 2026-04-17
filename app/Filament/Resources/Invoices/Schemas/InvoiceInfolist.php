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
                TextEntry::make('invoice_no'),
                TextEntry::make('date')
                    ->date(),
                TextEntry::make('due_date')
                    ->date(),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('currency_code'),
                TextEntry::make('exchange_rate')
                    ->numeric(),
                TextEntry::make('subtotal')
                    ->numeric(),
                TextEntry::make('tax_amount')
                    ->numeric(),
                TextEntry::make('total')
                    ->numeric(),
                TextEntry::make('paid_amount')
                    ->numeric(),
                TextEntry::make('balance_due')
                    ->numeric(),
                TextEntry::make('notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('posted_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_by')
                    ->numeric(),
                TextEntry::make('updated_by')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
