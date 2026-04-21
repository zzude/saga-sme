<?php

namespace App\Filament\Resources\BankReconciliations\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class BankReconciliationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('company.name')
                    ->label('Company'),
                TextEntry::make('account.name')
                    ->label('Account'),
                TextEntry::make('period.name')
                    ->label('Period'),
                TextEntry::make('statement_date')
                    ->date(),
                TextEntry::make('statement_balance')
                    ->numeric(),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('completed_by')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('completed_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_by')
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
