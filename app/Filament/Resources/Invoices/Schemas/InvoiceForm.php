<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Currency;
use App\Models\Customer;
use App\Services\ExchangeRateService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                // ── Section 1: Invoice Details ─────────────────────────────
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
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                // Recalculate due date
                                $set('due_date', now()->parse($state)->addDays(30)->format('Y-m-d'));
                                // Auto-fetch rate for new date if currency already selected
                                $currencyId = $get('currency_code');
                                if ($currencyId && $currencyId !== 'MYR') {
                                    self::fetchAndSetRate($set, $currencyId, $state);
                                }
                            }),

                        DatePicker::make('due_date')
                            ->label('Due Date')
                            ->required(),

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
                                    // MYR — lock rate to 1.0
                                    $set('currency_code', 'MYR');
                                    $set('exchange_rate', '1.000000');
                                    $set('exchange_rate_date', now()->format('Y-m-d'));
                                    $set('rate_source', 'AUTO');
                                    $set('override_reason', null);
                                } else {
                                    $set('currency_code', $state);
                                    $invoiceDate = $get('date') ?? now()->format('Y-m-d');
                                    self::fetchAndSetRate($set, $state, $invoiceDate);
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
                                // User manually edited rate — mark as override
                                $currencyId = $get('currency_code');
                                if ($currencyId && $currencyId !== 'MYR') {
                                    $set('rate_source', 'OVERRIDE');
                                    $set('rate_overridden_by', Auth::id());
                                    $set('rate_overridden_at', now()->toDateTimeString());
                                }
                                // Recalculate base totals
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


                        TextInput::make('rate_overridden_by')
                            ->hidden()
                            ->dehydrated(),

                        TextInput::make('rate_overridden_at')
                            ->hidden()
                            ->dehydrated(),
                    ]),

                // ── Section 3: Invoice Lines ───────────────────────────────
                Section::make('Invoice Lines')
                    ->schema([
                        Repeater::make('lines')
                            ->relationship('lines')
                            ->schema([
                                TextInput::make('description')
                                    ->label('Description')
                                    ->required()
                                    ->columnSpanFull(),

                                Select::make('account_id')
                                    ->label('Revenue Account')
                                    ->options(fn () => Account::where('company_id', Auth::user()->company_id)
                                        ->where('level', 3)
                                        ->where('type', 'revenue')
                                        ->pluck('name', 'id'))
                                    ->required()
                                    ->columnSpan(3),

                                TextInput::make('quantity')
                                    ->label('Qty')
                                    ->extraInputAttributes(['style' => 'text-align: right;'])
                                    ->numeric()
                                    ->default(1)
                                    ->live()
                                    ->afterStateUpdated(fn ($state, Set $set, Get $get) =>
                                        self::recalculateLine($set, $get))
                                    ->columnSpan(1),

                                TextInput::make('unit_price')
                                    ->label(fn (Get $get) => self::foreignLabel('Unit Price', $get))
                                    ->extraInputAttributes(['style' => 'text-align: right;'])
                                    ->numeric()
                                    ->default(0)
                                    ->live()
                                    ->afterStateUpdated(fn ($state, Set $set, Get $get) =>
                                        self::recalculateLine($set, $get))
                                    ->columnSpan(2),

                                TextInput::make('tax_amount')
                                    ->label(fn (Get $get) => self::foreignLabel('Tax', $get))
                                    ->extraInputAttributes(['style' => 'text-align: right;'])
                                    ->numeric()
                                    ->default(0)
                                    ->live()
                                    ->afterStateUpdated(fn ($state, Set $set, Get $get) =>
                                        self::recalculateLine($set, $get))
                                    ->columnSpan(2),

                                TextInput::make('amount')
                                    ->label(fn (Get $get) => self::foreignLabel('Amount', $get))
                                    ->extraInputAttributes(['style' => 'text-align: right;'])
                                    ->numeric()
                                    ->readOnly()
                                    ->columnSpan(2),

                                TextInput::make('line_total')
                                    ->label(fn (Get $get) => self::foreignLabel('Line Total', $get))
                                    ->extraInputAttributes(['style' => 'text-align: right;'])
                                    ->numeric()
                                    ->readOnly()
                                    ->columnSpan(2),

                                // MYR base columns — visible only for non-MYR invoices
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
                        // Foreign currency row
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

                        // MYR base row — visible only for non-MYR invoices
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

                        // Hidden foreign totals — always saved
                        TextInput::make('foreign_subtotal')->hidden()->dehydrated(),
                        TextInput::make('foreign_tax')->hidden()->dehydrated(),
                        TextInput::make('foreign_total')->hidden()->dehydrated(),
                    ]),

            ]);
    }

    // ── Private Helpers ────────────────────────────────────────────────────

    /** Fetch rate from ExchangeRateService and populate form fields */
    private static function fetchAndSetRate(Set $set, string $currencyCode, ?string $date): void
    {
        try {
            $rateDate = $date ? \Carbon\Carbon::parse($date) : now();
            $result = app(ExchangeRateService::class)->getRate($currencyCode, $rateDate);
            // $result = ['rate' => float, 'source' => string, 'date' => string]
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

    /** Recalculate a single line's amounts + base MYR amounts */
    private static function recalculateLine(Set $set, Get $get): void
    {
        $qty   = (float) ($get('quantity') ?? 0);
        $price = (float) ($get('unit_price') ?? 0);
        $tax   = (float) ($get('tax_amount') ?? 0);
        $rate  = (float) ($get('../../exchange_rate') ?? 1.0); // walk up to invoice level

        $amount    = $qty * $price;
        $lineTotal = $amount + $tax;

        $set('amount',    number_format($amount, 2, '.', ''));
        $set('line_total', number_format($lineTotal, 2, '.', ''));

        // Foreign = same as entered (user enters in foreign currency)
        $set('foreign_unit_price', number_format($price, 2, '.', ''));
        $set('foreign_line_total', number_format($lineTotal, 2, '.', ''));

        // Base MYR — Option B: round(line_total × rate)
        $set('base_unit_price', number_format(round($price * $rate, 2), 2, '.', ''));
        $set('base_line_total', number_format(round($lineTotal * $rate, 2), 2, '.', ''));
    }

    /** Recalculate all header totals after repeater change */
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

        // Foreign totals
        $set('subtotal',          number_format($subtotal, 2, '.', ''));
        $set('tax_amount',        number_format($tax, 2, '.', ''));
        $set('total',             number_format($total, 2, '.', ''));
        $set('foreign_subtotal',  number_format($subtotal, 2, '.', ''));
        $set('foreign_tax',       number_format($tax, 2, '.', ''));
        $set('foreign_total',     number_format($total, 2, '.', ''));

        // Base MYR totals
        $set('base_subtotal', number_format(round($subtotal * $rate, 2), 2, '.', ''));
        $set('base_tax',      number_format(round($tax * $rate, 2), 2, '.', ''));
        $set('base_total',    number_format(round($total * $rate, 2), 2, '.', ''));
    }

    /** Recalculate base totals only (when rate changes, lines unchanged) */
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

    /** Dynamic label suffix — shows currency code for non-MYR invoices */
    private static function foreignLabel(string $base, Get $get): string
    {
        $currency = $get('../../currency_code') ?? $get('currency_code') ?? 'MYR';
        return $currency === 'MYR'
            ? "{$base} (MYR)"
            : "{$base} ({$currency})";
    }

    /** True if invoice currency is non-MYR — used for ->visible() */
    private static function isNonMYR(Get $get): bool
    {
        $currency = $get('../../currency_code')   // from inside repeater
            ?? $get('currency_code')              // from top level
            ?? 'MYR';
        return $currency !== 'MYR';
    }
}
