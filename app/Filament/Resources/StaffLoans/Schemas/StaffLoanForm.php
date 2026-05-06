<?php

namespace App\Filament\Resources\StaffLoans\Schemas;

use App\Models\Account;
use App\Models\Employee;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Hidden;

class StaffLoanForm
{
    public static function schema(): array
    {
        return [
            Section::make('Loan Details')
                ->columns(2)
                ->components([
                    Select::make('employee_id')
                        ->label('Employee')
                        ->options(fn () => Employee::where('company_id', auth()->user()->company_id)
                            ->pluck('name', 'id'))
                        ->searchable()
                        ->required(),

                    Select::make('loan_type')
                        ->label('Loan Type')
                        ->options([
                            'personal'  => 'Personal',
                            'emergency' => 'Emergency',
                            'festival'  => 'Festival',
                        ])
                        ->required(),

                    TextInput::make('amount_applied')
                        ->label('Amount Applied (RM)')
                        ->numeric()
                        ->minValue(0.01)
                        ->required(),

                    TextInput::make('amount_approved')
                        ->label('Amount Approved (RM)')
                        ->numeric()
                        ->minValue(0.01)
                        ->nullable(),

                    TextInput::make('interest_rate')
                        ->label('Interest Rate (%)')
                        ->numeric()
                        ->default(0.00)
                        ->minValue(0)
                        ->suffix('%')
                        ->required(),

                    TextInput::make('tenure_months')
                        ->label('Tenure (Months)')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(60)
                        ->required(),

                    DatePicker::make('applied_date')
                        ->label('Applied Date')
                        ->default(today())
                        ->required(),

                    DatePicker::make('approved_date')
                        ->label('Approved Date')
                        ->nullable(),
                ]),

            Section::make('Account Mapping')
                ->columns(2)
                ->components([
                    Select::make('account_id')
                        ->label('Loan Receivable Account')
                        ->options(fn () => Account::where('company_id', auth()->user()->company_id)
                            ->where('code', '1180')
                            ->pluck('name', 'id'))
                        ->default(fn () => Account::where('company_id', auth()->user()->company_id)
                            ->where('code', '1180')
                            ->value('id'))
                        ->required(),

                    Select::make('disbursement_account_id')
                        ->label('Disbursement Account (Bank)')
                        ->options(fn () => Account::where('company_id', auth()->user()->company_id)
                            ->whereIn('code', ['1110', '1120'])
                            ->pluck('name', 'id'))
                        ->searchable()
                        ->required(),
                ]),

            Section::make('Notes')
                ->components([
                    Textarea::make('notes')
                        ->label('Notes')
                        ->rows(3)
                        ->nullable(),
                ]),

            Hidden::make('company_id')
                ->default(fn () => auth()->user()->company_id),
        ];
    }
}
