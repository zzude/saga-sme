<?php

namespace App\Filament\Resources\StaffLoans\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\BadgeEntry;

class StaffLoanInfolist
{
    public static function schema(): array
    {
        return [
            Section::make('Loan Information')
                ->columns(3)
                ->components([
                    TextEntry::make('loan_no')
                        ->label('Loan No'),

                    TextEntry::make('employee.name')
                        ->label('Employee'),

                    TextEntry::make('loan_type')
                        ->label('Type')
                        ->badge()
                        ->color(fn ($state) => match($state) {
                            'personal'  => 'info',
                            'emergency' => 'danger',
                            'festival'  => 'success',
                            default     => 'gray',
                        }),

                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->color(fn ($state) => match($state) {
                            'draft'     => 'gray',
                            'approved'  => 'info',
                            'disbursed' => 'warning',
                            'settled'   => 'success',
                            'rejected'  => 'danger',
                            default     => 'gray',
                        }),

                    TextEntry::make('amount_applied')
                        ->label('Amount Applied')
                        ->money('MYR'),

                    TextEntry::make('amount_approved')
                        ->label('Amount Approved')
                        ->money('MYR'),

                    TextEntry::make('interest_rate')
                        ->label('Interest Rate')
                        ->suffix('%'),

                    TextEntry::make('tenure_months')
                        ->label('Tenure')
                        ->suffix(' months'),

                    TextEntry::make('applied_date')
                        ->label('Applied Date')
                        ->date('d M Y'),
                ]),

            Section::make('Disbursement')
                ->columns(3)
                ->components([
                    TextEntry::make('approved_date')
                        ->label('Approved Date')
                        ->date('d M Y')
                        ->placeholder('—'),

                    TextEntry::make('disbursed_date')
                        ->label('Disbursed Date')
                        ->date('d M Y')
                        ->placeholder('—'),

                    TextEntry::make('approvedBy.name')
                        ->label('Approved By')
                        ->placeholder('—'),

                    TextEntry::make('account.name')
                        ->label('Loan Receivable Account'),

                    TextEntry::make('disbursementAccount.name')
                        ->label('Disbursement Account'),

                    TextEntry::make('journal.journal_no')
                        ->label('Disbursement Journal')
                        ->placeholder('—'),
                ]),

            Section::make('Summary')
                ->columns(3)
                ->components([
                    TextEntry::make('total_paid')
                        ->label('Total Paid')
                        ->money('MYR')
                        ->getStateUsing(fn ($record) => $record->total_paid),

                    TextEntry::make('outstanding_balance')
                        ->label('Outstanding Balance')
                        ->money('MYR')
                        ->getStateUsing(fn ($record) => $record->outstanding_balance)
                        ->color('danger'),

                    TextEntry::make('notes')
                        ->label('Notes')
                        ->placeholder('—')
                        ->columnSpanFull(),
                ]),
        ];
    }
}
