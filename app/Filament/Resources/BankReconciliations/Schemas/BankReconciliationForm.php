<?php

namespace App\Filament\Resources\BankReconciliations\Schemas;

use App\Models\Account;
use App\Models\AccountingPeriod;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class BankReconciliationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Reconciliation Details')
                    ->columns(2)
                    ->schema([
                        Select::make('account_id')
                            ->label('Bank Account')
                            ->options(fn () => Account::where('company_id', Auth::user()->company_id)
                                ->where('level', 3)
                                ->where('type', 'asset')
                                ->where(fn ($q) => $q
                                    ->where('name', 'like', '%Bank%')
                                    ->orWhere('name', 'like', '%Cash%')
                                )
                                ->pluck('name', 'id'))
                            ->required(),

                        Select::make('period_id')
                            ->label('Accounting Period')
                            ->options(fn () => AccountingPeriod::where('company_id', Auth::user()->company_id)
                                ->orderByDesc('start_date')
                                ->pluck('name', 'id'))
                            ->required(),

                        DatePicker::make('statement_date')
                            ->label('Statement Date')
                            ->default(now())
                            ->required(),

                        TextInput::make('statement_balance')
                            ->label('Statement Ending Balance (MYR)')
                            ->numeric()
                            ->default(0)
                            ->extraInputAttributes(['style' => 'text-align: right;'])
                            ->required(),

                        Select::make('status')
                            ->options([
                                'draft'       => 'Draft',
                                'in_progress' => 'In Progress',
                                'reconciled'  => 'Reconciled',
                                'locked'      => 'Locked',
                            ])
                            ->default('draft')
                            ->required(),

                        Textarea::make('notes')
                            ->label('Notes')
                            ->columnSpanFull()
                            ->nullable(),
                    ]),
            ]);
    }
}