<?php

namespace App\Filament\Resources\StaffLoans\Tables;

use App\Models\StaffLoan;
use App\Services\StaffLoanService;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;


class StaffLoansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('loan_no')
                    ->label('Loan No')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('employee.name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('loan_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'personal'  => 'info',
                        'emergency' => 'danger',
                        'festival'  => 'success',
                        default     => 'gray',
                    }),

                TextColumn::make('amount_approved')
                    ->label('Approved (RM)')
                    ->money('MYR')
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('tenure_months')
                    ->label('Tenure')
                    ->suffix(' mths')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'draft'     => 'gray',
                        'approved'  => 'info',
                        'disbursed' => 'warning',
                        'settled'   => 'success',
                        'rejected'  => 'danger',
                        default     => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('applied_date')
                    ->label('Applied')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('disbursed_date')
                    ->label('Disbursed')
                    ->date('d M Y')
                    ->placeholder('—')
                    ->sortable(),
            ])

            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft'     => 'Draft',
                        'approved'  => 'Approved',
                        'disbursed' => 'Disbursed',
                        'settled'   => 'Settled',
                        'rejected'  => 'Rejected',
                    ]),

                SelectFilter::make('loan_type')
                    ->options([
                        'personal'  => 'Personal',
                        'emergency' => 'Emergency',
                        'festival'  => 'Festival',
                    ]),
            ])

            ->actions([
                ViewAction::make(),

                EditAction::make()
                    ->visible(fn ($record) => $record->status === 'draft'),

                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'draft')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        app(StaffLoanService::class)->approve(
                            $record,
                            $record->amount_applied,
                            auth()->id()
                        );
                    }),

                Action::make('disburse')
                    ->label('Disburse')
                    ->icon('heroicon-o-banknotes')
                    ->color('warning')
                    ->visible(fn ($record) => $record->status === 'approved')
                    ->requiresConfirmation()
                    ->modalHeading('Confirm Loan Disbursement')
                    ->modalDescription(fn ($record) => 'Disburse RM ' . number_format($record->amount_approved, 2) . ' to ' . $record->employee->name . '? This will post a GL journal and generate the repayment schedule.')
                    ->action(function ($record) {
                        app(StaffLoanService::class)->disburse($record);
                    }),
            ])

            ->defaultSort('created_at', 'desc');
    }
}
