<?php

namespace App\Filament\Resources\Bills\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class BillInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('company.name')
                    ->label('Company'),
                TextEntry::make('vendor.name')
                    ->label('Vendor'),
                TextEntry::make('period.name')
                    ->label('Period'),
                TextEntry::make('bill_no'),
                TextEntry::make('reference_no')
                    ->placeholder('-'),
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
                TextEntry::make('journal_header_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('posted_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('approved_by')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('approved_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('voided_by')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('voided_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('void_reason')
                    ->placeholder('-'),
                TextEntry::make('created_by')
                    ->numeric()
                    ->placeholder('-'),
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
