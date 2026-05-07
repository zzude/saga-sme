<?php

namespace App\Filament\Resources\CashAdvances\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CashAdvanceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('company.name')
                    ->label('Company'),
                TextEntry::make('employee.name')
                    ->label('Employee'),
                TextEntry::make('advance_no'),
                TextEntry::make('purpose'),
                TextEntry::make('amount_requested')
                    ->numeric(),
                TextEntry::make('amount_approved')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('amount_settled')
                    ->numeric(),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('applied_date')
                    ->date(),
                TextEntry::make('approved_date')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('disbursed_date')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('due_date')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('account.name')
                    ->label('Account')
                    ->placeholder('-'),
                TextEntry::make('journal.id')
                    ->label('Journal')
                    ->placeholder('-'),
                TextEntry::make('approved_by')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('disbursed_by')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('created_by')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
