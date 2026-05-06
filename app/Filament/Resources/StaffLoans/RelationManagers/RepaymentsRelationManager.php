<?php

namespace App\Filament\Resources\StaffLoans\RelationManagers;

use App\Services\StaffLoanService;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class RepaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'repayments';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return 'Repayment Schedule';
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('installment_no')
                    ->label('#')
                    ->sortable(),

                TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('principal_amount')
                    ->label('Principal (RM)')
                    ->money('MYR'),

                TextColumn::make('interest_amount')
                    ->label('Interest (RM)')
                    ->money('MYR'),

                TextColumn::make('total_amount')
                    ->label('Total (RM)')
                    ->money('MYR')
                    ->weight('bold'),

                TextColumn::make('paid_amount')
                    ->label('Paid (RM)')
                    ->money('MYR'),

                TextColumn::make('balance_after')
                    ->label('Balance After (RM)')
                    ->money('MYR'),

                TextColumn::make('paid_date')
                    ->label('Paid Date')
                    ->date('d M Y')
                    ->placeholder('—'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'pending' => 'warning',
                        'paid'    => 'success',
                        'overdue' => 'danger',
                        default   => 'gray',
                    }),
            ])

            ->actions([
                ViewAction::make()
                    ->infolist([
                        TextEntry::make('installment_no')->label('Installment #'),
                        TextEntry::make('due_date')->label('Due Date')->date('d M Y'),
                        TextEntry::make('principal_amount')->label('Principal')->money('MYR'),
                        TextEntry::make('interest_amount')->label('Interest')->money('MYR'),
                        TextEntry::make('total_amount')->label('Total')->money('MYR'),
                        TextEntry::make('paid_amount')->label('Paid')->money('MYR'),
                        TextEntry::make('balance_after')->label('Balance After')->money('MYR'),
                        TextEntry::make('paid_date')->label('Paid Date')->date('d M Y')->placeholder('—'),
                        TextEntry::make('status')->label('Status')->badge(),
                        TextEntry::make('journal.journal_no')->label('Journal')->placeholder('—'),
                    ]),

                Action::make('mark_paid')
                    ->label('Mark Paid')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => in_array($record->status, ['pending', 'overdue']))
                    ->requiresConfirmation()
                    ->modalHeading('Mark Repayment as Paid')
                    ->modalDescription(fn ($record) => 'Mark Instalment #' . $record->installment_no . ' (RM ' . number_format($record->total_amount, 2) . ') as paid? This will post a GL journal.')
                    ->action(function ($record) {
                        app(StaffLoanService::class)->markRepaymentPaid($record);
                    }),
            ])

            ->defaultSort('installment_no', 'asc');
    }
}
