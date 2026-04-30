<?php

namespace App\Filament\Resources\Bills\Schemas;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Currency;
use App\Models\Vendor;
use App\Services\ExchangeRateService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class BillForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                // ── Section 1: Bill Details ────────────────────────────────
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
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                $currencyCode = $get('currency_code');
                                if ($currencyCode && $currencyCode !== 'MYR') {
                                    self::fetchAndSetRate($set, $currencyCode, $state);
                                }
                            }),

                        DatePicker::make('due_date')
                            ->label('Due Date')
                            ->required(),

                        TextInput::make('reference_no')
                            ->label('Vendor Invoice No')
                            ->nullable()
                            ->placeholder("Vendor's invoice number"),

                        Textarea::make('notes')
                            ->label('Notes')
                            ->columnSpanFull()
                            ->nullable(),
                    ]),

                // ── Section 2: Currency & Exchange Rate ────────────────────
                Section::make('Currency & Exchange Rate')
                    ->columns(4)
                    ->schema([
                        Select::make('currency_code')
                            ->label('Currency')
                            ->options(fn () => Currency::where('is_active', true)
                                ->orderBy('code')
                                ->pluck('code', 'code'))
                            ->default('MYR')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                if (!$state || $state === 'MYR') {
                                    $set('exchange_rate', '1.000000');
                                    $set('exchange_rate_date', now()->format('Y-m-d'));
                                    $set('rate_source', 'AUTO');
                                    $set('override_reason', null);
                                } else {
                                    $billDate = $get('date') ?? now()->format('Y-m-d');
                                    self::fetchAndSetRate($set, $state, $billDate);
                                }
                            }),

                        TextInput::make('exchange_rate')
                            ->label('Exchange Rate (to MYR)')
                            ->numeric()
                            ->default('1.000000')
                            ->required()
                            ->live()
                            ->extraInputAttributes(['style' => 'text-align: right;'])
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                $currencyCode = $get('currency_code');
                                if ($currencyCode && $currencyCode !== 'MYR') {
                                    $set('rate_source', 'OVERRIDE');
                                    $set('rate_overridden_by', Auth::id());
                                    $set('rate_overridden_at', now()->toDateTimeString());
                                }
                                self::recalculateBaseTotals($set, $get);
                            })
                            ->helperText(fn (Get $get) => $get('rate_source') === 'OVERRIDE'
                                ? '⚠️ Manual override — original rate replaced'
                                : '✓ Auto-fetched from Frankfurter API'),

                        DatePicker::make('exchange_rate_date')
                            ->label('Rate Date')
                            ->default(now())
                            ->readOnly()
                            ->helperText('Snapshot date of the rate used'),

                        Select::make('rate_source')
                            ->label('Rate Source')
                            ->options([
                                'AUTO'     => 'Auto (API)',
                                'MANUAL'   => 'Manual',
                                'OVERRIDE' => 'Override',
                            ])
                            ->default('AUTO')
                            ->disabled()
                            ->dehydrated(),

                        TextInput::make('override_reason')
                            ->label('Override Reason')
                            ->columnSpanFull()
                            ->nullable()
                            ->visible(fn (Get $get) => $get('rate_source') === 'OVERRIDE')
                            ->placeholder('Reason for manual rate override...'),

                        // Hidden audit fields
                        TextInput::make('rate_overridden_by')->hidden()->dehydrated(),
                        TextInput::make('rate_overridden_at')->hidden()->dehydrated(),
                    ]),

                // ── Section 3: Bill Lines ──────────────────────────────────
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
                                    ->afterStateUpdated(fn ($state, Set $set, Get $get) =>
                                        self::recalculateLine($set, $get))
                                    ->columnSpan(1),

                                TextInput::make('unit_price')
                                    ->label(fn (Get $get) => self::foreignLabel('Unit Price', $get))
                                    ->numeric()
                                    ->default(0)
                                    ->extraInputAttributes(['style' => 'text-align: right;'])
                                    ->live()
                                    ->afterStateUpdated(fn ($state, Set $set, Get $get) =>
                                        self::recalculateLine($set, $get))
                                    ->columnSpan(2),

                                TextInput::make('tax_amount')
                                    ->label(fn (Get $get) => self::foreignLabel('Tax', $get))
                                    ->numeric()
                                    ->default(0)
                                    ->extraInputAttributes(['style' => 'text-align: right;'])
                                    ->live()
                                    ->afterStateUpdated(fn ($state, Set $set, Get $get) =>
                                        self::recalculateLine($set, $get))
                                    ->columnSpan(2),

                                TextInput::make('amount')
                                    ->label(fn (Get $get) => self::foreignLabel('Amount', $get))
                                    ->numeric()
                                    ->readOnly()
                                    ->extraInputAttributes(['style' => 'text-align: right;'])
                                    ->columnSpan(2),

                                TextInput::make('line_total')
                                    ->label(fn (Get $get) => self::foreignLabel('Line Total', $get))
                                    ->numeric()
                                    ->readOnly()
                                    ->extraInputAttributes(['style' => 'text-align: right;'])
                                    ->columnSpan(2),

                                // MYR base columns — visible for non-MYR only
                                TextInput::make('base_unit_price')
                                    ->label('Unit Price (MYR)')
                                    ->extraInputAttributes(['style' => 'text-align: right; background: #f0fdf4;'])
                                    ->numeric()
                                    ->readOnly()
                                    ->columnSpan(2)
                                    ->visible(fn (Get $get) => self::isNonMYR($get)),

                                TextInput::make('base_line_total')
                                    ->label('Line Total (MYR)')
                                    ->extraInputAttributes(['style' => 'text-align: right; background: #f0fdf4;'])
                                    ->numeric()
                                    ->readOnly()
                                    ->columnSpan(2)
                                    ->visible(fn (Get $get) => self::isNonMYR($get)),
                            ])
                            ->columns(6)
                            ->addActionLabel('+ Add Line')
                            ->reorderable('sort_order')
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                self::recalculateAllTotals($state, $set, $get);
                            }),
                    ]),

                // ── Section 4: Totals ──────────────────────────────────────
                Section::make('Totals')
                    ->columns(3)
                    ->schema([
                        TextInput::make('subtotal')
                            ->label(fn (Get $get) => self::foreignLabel('Subtotal', $get))
                            ->extraInputAttributes(['style' => 'text-align: right;'])
                            ->numeric()
                            ->default(0)
                            ->readOnly(),

                        TextInput::make('tax_amount')
                            ->label(fn (Get $get) => self::foreignLabel('Tax Amount', $get))
                            ->extraInputAttributes(['style' => 'text-align: right;'])
                            ->numeric()
                            ->default(0)
                            ->readOnly(),

                        TextInput::make('total')
                            ->label(fn (Get $get) => self::foreignLabel('Total', $get))
                            ->extraInputAttributes(['style' => 'text-align: right;'])
                            ->numeric()
                            ->default(0)
                            ->readOnly(),

                        // MYR base row — non-MYR only
                        TextInput::make('base_subtotal')
                            ->label('Subtotal (MYR)')
                            ->extraInputAttributes(['style' => 'text-align: right; background: #f0fdf4;'])
                            ->numeric()
                            ->default(0)
                            ->readOnly()
                            ->visible(fn (Get $get) => self::isNonMYR($get)),

                        TextInput::make('base_tax')
                            ->label('Tax Amount (MYR)')
                            ->extraInputAttributes(['style' => 'text-align: right; background: #f0fdf4;'])
                            ->numeric()
                            ->default(0)
                            ->readOnly()
                            ->visible(fn (Get $get) => self::isNonMYR($get)),

                        TextInput::make('base_total')
                            ->label('Total (MYR) — GL Amount')
                            ->extraInputAttributes(['style' => 'text-align: right; background: #f0fdf4; font-weight: bold;'])
                            ->numeric()
                            ->default(0)
                            ->readOnly()
                            ->visible(fn (Get $get) => self::isNonMYR($get)),

                        // Hidden foreign totals
                        TextInput::make('foreign_subtotal')->hidden()->dehydrated(),
                        TextInput::make('foreign_tax')->hidden()->dehydrated(),
                        TextInput::make('foreign_total')->hidden()->dehydrated(),
                    ]),

            ]);
    }

    // ── Private Helpers ────────────────────────────────────────────────────

    private static function fetchAndSetRate(Set $set, string $currencyCode, ?string $date): void
    {
        try {
            $rateDate = $date ? \Carbon\Carbon::parse($date) : now();
            $result = app(ExchangeRateService::class)->getRate($currencyCode, $rateDate);
            if (!empty($result['rate'])) {
                $set('exchange_rate',      number_format((float) $result['rate'], 6, '.', ''));
                $set('exchange_rate_date', $result['date'] ?? $rateDate->format('Y-m-d'));
                $set('rate_source',        $result['source'] ?? 'AUTO');
            } else {
                $set('rate_source', 'MANUAL');
            }
        } catch (\Throwable $e) {
            $set('rate_source', 'MANUAL');
        }
    }

    private static function recalculateLine(Set $set, Get $get): void
    {
        $qty   = (float) ($get('quantity') ?? 0);
        $price = (float) ($get('unit_price') ?? 0);
        $tax   = (float) ($get('tax_amount') ?? 0);
        $rate  = (float) ($get('../../exchange_rate') ?? 1.0);

        $amount    = $qty * $price;
        $lineTotal = $amount + $tax;

        $set('amount',    number_format($amount, 2, '.', ''));
        $set('line_total', number_format($lineTotal, 2, '.', ''));

        $set('foreign_unit_price', number_format($price, 2, '.', ''));
        $set('foreign_line_total', number_format($lineTotal, 2, '.', ''));

        // Option B rounding
        $set('base_unit_price', number_format(round($price * $rate, 2), 2, '.', ''));
        $set('base_line_total', number_format(round($lineTotal * $rate, 2), 2, '.', ''));
    }

    private static function recalculateAllTotals(array $state, Set $set, Get $get): void
    {
        $rate     = (float) ($get('exchange_rate') ?? 1.0);
        $subtotal = 0;
        $tax      = 0;

        foreach ($state as $line) {
            $qty       = (float) ($line['quantity'] ?? 0);
            $price     = (float) ($line['unit_price'] ?? 0);
            $taxAmt    = (float) ($line['tax_amount'] ?? 0);
            $subtotal += $qty * $price;
            $tax      += $taxAmt;
        }

        $total = $subtotal + $tax;

        $set('subtotal',         number_format($subtotal, 2, '.', ''));
        $set('tax_amount',       number_format($tax, 2, '.', ''));
        $set('total',            number_format($total, 2, '.', ''));
        $set('foreign_subtotal', number_format($subtotal, 2, '.', ''));
        $set('foreign_tax',      number_format($tax, 2, '.', ''));
        $set('foreign_total',    number_format($total, 2, '.', ''));

        $set('base_subtotal', number_format(round($subtotal * $rate, 2), 2, '.', ''));
        $set('base_tax',      number_format(round($tax * $rate, 2), 2, '.', ''));
        $set('base_total',    number_format(round($total * $rate, 2), 2, '.', ''));
    }

    private static function recalculateBaseTotals(Set $set, Get $get): void
    {
        $rate     = (float) ($get('exchange_rate') ?? 1.0);
        $subtotal = (float) ($get('subtotal') ?? 0);
        $tax      = (float) ($get('tax_amount') ?? 0);
        $total    = $subtotal + $tax;

        $set('base_subtotal', number_format(round($subtotal * $rate, 2), 2, '.', ''));
        $set('base_tax',      number_format(round($tax * $rate, 2), 2, '.', ''));
        $set('base_total',    number_format(round($total * $rate, 2), 2, '.', ''));
    }

    private static function foreignLabel(string $base, Get $get): string
    {
        $currency = $get('../../currency_code') ?? $get('currency_code') ?? 'MYR';
        return $currency === 'MYR'
            ? "{$base} (MYR)"
            : "{$base} ({$currency})";
    }

    private static function isNonMYR(Get $get): bool
    {
        $currency = $get('../../currency_code')
            ?? $get('currency_code')
            ?? 'MYR';
        return $currency !== 'MYR';
    }
}
