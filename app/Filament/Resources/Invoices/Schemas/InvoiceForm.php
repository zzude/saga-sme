<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Customer;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Invoice Details')
                    ->columns(2)
                    ->schema([
                    TextInput::make('invoice_no')
                        ->label('Invoice No')
                        ->required()
                        ->placeholder('INV-2026-0001')
                        ->default(function () {
                            $companyId = Auth::user()->company_id;
                            $year = now()->format('Y');
                            $latest = \App\Models\Invoice::where('company_id', $companyId)
                                ->whereYear('created_at', $year)
                                ->orderByDesc('id')
                                ->first();
                            $nextNo = $latest
                                ? (int) substr($latest->invoice_no, -4) + 1
                                : 1;
                            return 'INV-' . $year . '-' . str_pad($nextNo, 4, '0', STR_PAD_LEFT);
                        })
                        ->maxLength(30),

                        Select::make('status')
                            ->options([
                                'draft'   => 'Draft',
                                'sent'    => 'Sent',
                                'partial' => 'Partial',
                                'paid'    => 'Paid',
                                'overdue' => 'Overdue',
                                'void'    => 'Void',
                            ])
                            ->default('draft')
                            ->required(),

                        Select::make('customer_id')
                            ->label('Customer')
                            ->options(fn () => Customer::where('company_id', Auth::user()->company_id)
                                ->where('is_active', true)
                                ->pluck('name', 'id'))
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if ($state) {
                                    $customer = Customer::find($state);
                                    if ($customer) {
                                        $set('due_date', now()->addDays($customer->credit_term_days)->format('Y-m-d'));
                                    }
                                }
                            }),

                        Select::make('period_id')
                            ->label('Accounting Period')
                            ->options(fn () => AccountingPeriod::where('company_id', Auth::user()->company_id)
                                ->orderByDesc('start_date')
                                ->pluck('name', 'id'))
                            ->required(),

                        DatePicker::make('date')
                            ->label('Invoice Date')
                            ->default(now())
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                $set('due_date', now()->parse($state)->addDays(30)->format('Y-m-d'));
                            }),

                        DatePicker::make('due_date')
                            ->label('Due Date')
                            ->required(),

                        Textarea::make('notes')
                            ->label('Notes')
                            ->columnSpanFull()
                            ->nullable(),
                    ]),

                Section::make('Invoice Lines')
                    ->schema([
                        Repeater::make('lines')
                            ->relationship('lines')
                            ->schema([
                                TextInput::make('description')
                                    ->label('Description')
                                    ->required()
                                    ->columnSpan(3),

                                Select::make('account_id')
                                    ->label('Revenue Account')
                                    ->options(fn () => Account::where('company_id', Auth::user()->company_id)
                                        ->where('level', 3)
                                        ->where('type', 'revenue')
                                        ->pluck('name', 'id'))
                                    ->required()
                                    ->columnSpan(2),

                                TextInput::make('quantity')
                                    ->label('Qty')
                                    ->numeric()
                                    ->default(1)
                                    ->live()
                                    ->afterStateUpdated(fn ($state, Set $set, $get) =>
                                        self::recalculateLine($set, $get))
                                    ->columnSpan(1),

                                TextInput::make('unit_price')
                                    ->label('Unit Price')
                                    ->numeric()
                                    ->default(0)
                                    ->live()
                                    ->afterStateUpdated(fn ($state, Set $set, $get) =>
                                        self::recalculateLine($set, $get))
                                    ->columnSpan(2),

                                TextInput::make('tax_amount')
                                    ->label('Tax (MYR)')
                                    ->numeric()
                                    ->default(0)
                                    ->live()
                                    ->afterStateUpdated(fn ($state, Set $set, $get) =>
                                        self::recalculateLine($set, $get))
                                    ->columnSpan(1),

                                TextInput::make('amount')
                                    ->label('Amount')
                                    ->numeric()
                                    ->readOnly()
                                    ->columnSpan(2),

                                TextInput::make('line_total')
                                    ->label('Line Total')
                                    ->numeric()
                                    ->readOnly()
                                    ->columnSpan(1),
                            ])
                            ->columns(12)
                            ->addActionLabel('+ Add Line')
                            ->reorderable('sort_order'),
                    ]),

                Section::make('Totals')
                    ->columns(3)
                    ->schema([
                        TextInput::make('subtotal')
                            ->label('Subtotal (MYR)')
                            ->numeric()
                            ->default(0)
                            ->readOnly(),

                        TextInput::make('tax_amount')
                            ->label('Tax Amount (MYR)')
                            ->numeric()
                            ->default(0)
                            ->readOnly(),

                        TextInput::make('total')
                            ->label('Total (MYR)')
                            ->numeric()
                            ->default(0)
                            ->readOnly(),
                    ]),

            ]);
    }

    private static function recalculateLine(Set $set, $get): void
    {
        $qty      = (float) ($get('quantity') ?? 0);
        $price    = (float) ($get('unit_price') ?? 0);
        $tax      = (float) ($get('tax_amount') ?? 0);
        $amount   = $qty * $price;
        $set('amount', number_format($amount, 2, '.', ''));
        $set('line_total', number_format($amount + $tax, 2, '.', ''));
    }
}