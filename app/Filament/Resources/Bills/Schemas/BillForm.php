<?php

namespace App\Filament\Resources\Bills\Schemas;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Vendor;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class BillForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Bill Details')
                    ->columns(2)
                    ->schema([
                        TextInput::make('bill_no')
                            ->label('Bill No')
                            ->required()
                            ->placeholder('BILL-2026-0001')
                            ->maxLength(30)
                            ->default(function () {
                                $companyId = Auth::user()->company_id;
                                $year = now()->format('Y');
                                $latest = \App\Models\Bill::where('company_id', $companyId)
                                    ->whereYear('created_at', $year)
                                    ->orderByDesc('id')
                                    ->first();
                                $nextNo = $latest
                                    ? (int) substr($latest->bill_no, -4) + 1
                                    : 1;
                                return 'BILL-' . $year . '-' . str_pad($nextNo, 4, '0', STR_PAD_LEFT);
                            }),

                        Select::make('status')
                            ->options([
                                'draft'     => 'Draft',
                                'submitted' => 'Submitted',
                                'approved'  => 'Approved',
                                'partial'   => 'Partial',
                                'paid'      => 'Paid',
                                'overdue'   => 'Overdue',
                                'void'      => 'Void',
                            ])
                            ->default('draft')
                            ->required(),

                        Select::make('vendor_id')
                            ->label('Vendor')
                            ->options(fn () => Vendor::where('company_id', Auth::user()->company_id)
                                ->where('is_active', true)
                                ->pluck('name', 'id'))
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if ($state) {
                                    $vendor = Vendor::find($state);
                                    if ($vendor) {
                                        $set('due_date', now()->addDays($vendor->credit_term_days)->format('Y-m-d'));
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
                            ->label('Bill Date')
                            ->default(now())
                            ->required(),

                        DatePicker::make('due_date')
                            ->label('Due Date')
                            ->required(),

                        TextInput::make('reference_no')
                            ->label('Vendor Invoice No')
                            ->nullable()
                            ->placeholder('Vendor\'s invoice number'),

                        Textarea::make('notes')
                            ->label('Notes')
                            ->columnSpanFull()
                            ->nullable(),
                    ]),

                Section::make('Bill Lines')
                    ->schema([
                        Repeater::make('lines')
                            ->relationship('lines')
                            ->schema([
                                TextInput::make('description')
                                    ->label('Description')
                                    ->required()
                                    ->columnSpanFull(),

                                Select::make('account_id')
                                    ->label('Expense Account')
                                    ->options(fn () => Account::where('company_id', Auth::user()->company_id)
                                        ->where('level', 3)
                                        ->where('type', 'expense')
                                        ->pluck('name', 'id'))
                                    ->required()
                                    ->columnSpan(3),

                                TextInput::make('quantity')
                                    ->label('Qty')
                                    ->numeric()
                                    ->default(1)
                                    ->extraInputAttributes(['style' => 'text-align: right;'])
                                    ->live()
                                    ->afterStateUpdated(fn ($state, Set $set, $get) =>
                                        self::recalculateLine($set, $get))
                                    ->columnSpan(1),

                                TextInput::make('unit_price')
                                    ->label('Unit Price (MYR)')
                                    ->numeric()
                                    ->default(0)
                                    ->extraInputAttributes(['style' => 'text-align: right;'])
                                    ->live()
                                    ->afterStateUpdated(fn ($state, Set $set, $get) =>
                                        self::recalculateLine($set, $get))
                                    ->columnSpan(2),

                                TextInput::make('tax_amount')
                                    ->label('Tax (MYR)')
                                    ->numeric()
                                    ->default(0)
                                    ->extraInputAttributes(['style' => 'text-align: right;'])
                                    ->live()
                                    ->afterStateUpdated(fn ($state, Set $set, $get) =>
                                        self::recalculateLine($set, $get))
                                    ->columnSpan(2),

                                TextInput::make('amount')
                                    ->label('Amount (MYR)')
                                    ->numeric()
                                    ->readOnly()
                                    ->extraInputAttributes(['style' => 'text-align: right;'])
                                    ->columnSpan(2),

                                TextInput::make('line_total')
                                    ->label('Line Total (MYR)')
                                    ->numeric()
                                    ->readOnly()
                                    ->extraInputAttributes(['style' => 'text-align: right;'])
                                    ->columnSpan(2),
                            ])
                            ->columns(6)
                            ->addActionLabel('+ Add Line')
                            ->reorderable('sort_order')
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                $subtotal = 0;
                                $tax      = 0;

                                foreach ($state as $line) {
                                    $qty      = (float) ($line['quantity'] ?? 0);
                                    $price    = (float) ($line['unit_price'] ?? 0);
                                    $taxAmt   = (float) ($line['tax_amount'] ?? 0);
                                    $amount   = $qty * $price;
                                    $subtotal += $amount;
                                    $tax      += $taxAmt;
                                }

                                $set('subtotal', number_format($subtotal, 2, '.', ''));
                                $set('tax_amount', number_format($tax, 2, '.', ''));
                                $set('total', number_format($subtotal + $tax, 2, '.', ''));
                            }),
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
        $qty    = (float) ($get('quantity') ?? 0);
        $price  = (float) ($get('unit_price') ?? 0);
        $tax    = (float) ($get('tax_amount') ?? 0);
        $amount = $qty * $price;
        $set('amount', number_format($amount, 2, '.', ''));
        $set('line_total', number_format($amount + $tax, 2, '.', ''));
    }
}