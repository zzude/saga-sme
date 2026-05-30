<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuotationResource\Pages;
use App\Models\Customer;
use App\Models\Quotation;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
//
//
//

use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;


use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class QuotationResource extends Resource
{
    protected static ?string $model = Quotation::class;

    public static function getNavigationGroup(): string  { return 'Jualan'; }
    public static function getNavigationIcon(): string   { return 'heroicon-o-document-text'; }
    public static function getNavigationLabel(): string  { return 'Sebut Harga'; }
    public static function getNavigationSort(): int      { return 1; }
    public static function getModelLabel(): string       { return 'Sebut Harga'; }
    public static function getPluralModelLabel(): string { return 'Sebut Harga'; }

    // =========================================================================
    // FORM
    // =========================================================================
    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            // ── Header ───────────────────────────────────────────────────────
            Section::make('Maklumat Sebut Harga')
                ->columns(3)
                ->schema([
                    TextInput::make('quotation_number')
                        ->label('No. Sebut Harga')
                        ->disabled()
                        ->dehydrated(false)
                        ->placeholder('Auto-generate'),

                    DatePicker::make('quotation_date')
                        ->label('Tarikh')
                        ->required()
                        ->default(now()),

                    DatePicker::make('valid_until')
                        ->label('Sah Hingga')
                        ->required()
                        ->default(now()->addDays(30)),

                    TextInput::make('title')
                        ->label('Tajuk / Perkara')
                        ->columnSpanFull()
                        ->placeholder('e.g. Supply of IT Equipment...'),
                ]),

            // ── Customer ─────────────────────────────────────────────────────
            Section::make('Maklumat Pelanggan')
                ->columns(2)
                ->schema([
                    Select::make('customer_id')
                        ->label('Pelanggan')
                        ->options(fn () => Customer::where('company_id', auth()->user()->company_id)
                            ->orderBy('name')
                            ->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state) {
                                $c = Customer::find($state);
                                if ($c) {
                                    $set('customer_name',    $c->name);
                                    $set('customer_address', $c->address);
                                }
                            }
                        }),

                    TextInput::make('attention_to')
                        ->label('Perhatian (Attn)')
                        ->placeholder('Nama pegawai / wakil'),

                    TextInput::make('customer_name')
                        ->label('Nama Syarikat')
                        ->required()
                        ->readOnly(fn ($get) => (bool) $get('customer_id')),

                    Textarea::make('customer_address')
                        ->label('Alamat')
                        ->rows(3)
                        ->readOnly(fn ($get) => (bool) $get('customer_id')),
                ]),

            // ── Items Repeater ───────────────────────────────────────────────
            // FIX: Placeholder::make('line_no') REMOVED — was root cause of
            // memory exhaustion via recursive Livewire hydration spiral.
            // line_no is assigned in afterCreate() / afterSave() instead.
            Section::make('Senarai Item')
                ->schema([
                    Repeater::make('items')
                        ->relationship('items')
                        ->label('')
                        ->schema([
                            // Row 1 — Description + Detail
                            Grid::make(12)->schema([
                                TextInput::make('description')
                                    ->label('Penerangan')
                                    ->required()
                                    ->columnSpan(7),

                                TextInput::make('detail')
                                    ->label('Spesifikasi')
                                    ->columnSpan(5),
                            ]),
                            // Row 2 — Numeric fields
                            Grid::make(12)->schema([
                                TextInput::make('unit_of_measure')
                                    ->label('Unit')
                                    ->default('unit')
                                    ->columnSpan(2),

                                TextInput::make('quantity')
                                    ->label('Kuantiti')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->columnSpan(2),

                                TextInput::make('unit_price')
                                    ->label('Harga Unit (RM)')
                                    ->numeric()
                                    ->required()
                                    ->default(0)
                                    ->columnSpan(3),

                                TextInput::make('discount_percent')
                                    ->label('Diskaun %')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('%')
                                    ->columnSpan(2),

                                Toggle::make('is_sst_applicable')
                                    ->label('SST')
                                    ->default(false)
                                    ->live()
                                    ->columnSpan(1),

                                TextInput::make('sst_rate')
                                    ->label('Kadar SST %')
                                    ->numeric()
                                    ->default(8)
                                    ->suffix('%')
                                    ->visible(fn ($get) => (bool) $get('is_sst_applicable'))
                                    ->columnSpan(2),
                            ]),
                        ])
                        ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                            return self::calculateItemAmounts($data);
                        })
                        ->mutateRelationshipDataBeforeSaveUsing(function (array $data): array {
                            return self::calculateItemAmounts($data);
                        })
                        ->addActionLabel('+ Tambah Item')
                        ->collapsible()
                        ->defaultItems(0)
                        ->reorderable(false),
                ]),

            // ── Summary ──────────────────────────────────────────────────────
            Section::make('Ringkasan Jumlah')
                ->columns(3)
                ->schema([
                    Toggle::make('sst_applicable')
                        ->label('SST Dikenakan?')
                        ->default(false),

                    TextInput::make('sst_rate')
                        ->label('Kadar SST %')
                        ->numeric()
                        ->default(8)
                        ->suffix('%'),

                    TextInput::make('payment_terms_days')
                        ->label('Terma Pembayaran')
                        ->numeric()
                        ->default(30)
                        ->suffix('hari'),

                    TextInput::make('subtotal')
                        ->label('Subtotal (RM)')
                        ->prefix('RM')
                        ->disabled()
                        ->dehydrated(false)
                        ->default('0.00'),

                    TextInput::make('discount_amount')
                        ->label('Diskaun (RM)')
                        ->prefix('RM')
                        ->disabled()
                        ->dehydrated(false)
                        ->default('0.00'),

                    TextInput::make('total_amount')
                        ->label('JUMLAH KESELURUHAN (RM)')
                        ->prefix('RM')
                        ->disabled()
                        ->dehydrated(false)
                        ->default('0.00')
                        ->extraInputAttributes(['class' => 'font-bold text-primary-600']),
                ]),

            // ── Terms ─────────────────────────────────────────────────────────
            Section::make('Terma & Catatan')
                ->columns(2)
                ->schema([
                    Textarea::make('terms_conditions')
                        ->label('Terma & Syarat')
                        ->rows(4)
                        ->placeholder("1. Harga adalah termasuk penghantaran ke tapak.\n2. Tempoh jaminan: 1 tahun.\n3. Bayaran dalam masa 30 hari dari tarikh invois."),

                    Textarea::make('remarks')
                        ->label('Catatan (pada PDF)')
                        ->rows(4),

                    Textarea::make('notes')
                        ->label('Nota Dalaman (tidak pada PDF)')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    // =========================================================================
    // ITEM AMOUNT CALCULATOR — shared by create + save mutators
    // =========================================================================
    protected static function calculateItemAmounts(array $data): array
    {
        $qty      = (float) ($data['quantity']          ?? 1);
        $price    = (float) ($data['unit_price']        ?? 0);
        $discount = (float) ($data['discount_percent']  ?? 0);
        $isSst    = (bool)  ($data['is_sst_applicable'] ?? false);
        $sstRate  = $isSst ? (float) ($data['sst_rate'] ?? 8) : 0;

        $gross   = round($qty * $price, 2);
        $discAmt = round($gross * $discount / 100, 2);
        $net     = round($gross - $discAmt, 2);
        $sstAmt  = $isSst ? round($net * $sstRate / 100, 2) : 0;
        $total   = round($net + $sstAmt, 2);

        return array_merge($data, [
            'company_id'      => auth()->user()->company_id,
            'line_no'         => 0,   // placeholder — renumbered in afterCreate/afterSave
            'gross_amount'    => $gross,
            'discount_amount' => $discAmt,
            'net_amount'      => $net,
            'sst_rate'        => $sstRate,
            'sst_amount'      => $sstAmt,
            'total_amount'    => $total,
        ]);
    }

    // =========================================================================
    // INFOLIST (View Page)
    // =========================================================================
    public static function infolist(Schema $schema): Schema
    {
        return \App\Filament\Resources\QuotationResource\Schemas\QuotationInfolist::configure($schema);
    }

    // =========================================================================
    // TABLE
    // =========================================================================
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('quotation_number')
                    ->label('No. Sebut Harga')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

                TextColumn::make('quotation_date')
                    ->label('Tarikh')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('customer_name')
                    ->label('Pelanggan')
                    ->searchable()
                    ->limit(30),

                TextColumn::make('title')
                    ->label('Perkara')
                    ->limit(35)
                    ->tooltip(fn ($record) => $record->title),

                TextColumn::make('total_amount')
                    ->label('Jumlah (RM)')
                    ->money('MYR')
                    ->sortable()
                    ->alignRight()
                    ->weight('bold'),

                TextColumn::make('valid_until')
                    ->label('Sah Hingga')
                    ->date('d/m/Y')
                    ->color(fn ($record) => $record->is_expired ? 'danger' : null),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'draft'     => 'gray',
                        'sent'      => 'info',
                        'accepted'  => 'success',
                        'rejected'  => 'danger',
                        'expired'   => 'warning',
                        'cancelled' => 'gray',
                        'converted' => 'purple',
                        default     => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'draft'     => 'Draf',
                        'sent'      => 'Dihantar',
                        'accepted'  => 'Diterima',
                        'rejected'  => 'Ditolak',
                        'expired'   => 'Tamat Tempoh',
                        'cancelled' => 'Dibatal',
                        'converted' => 'Ditukar ke Invois',
                        default     => $state,
                    }),

                TextColumn::make('revision')
                    ->label('Semakan')
                    ->formatStateUsing(fn ($state) => $state > 0 ? "Rev {$state}" : 'Asal')
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft'     => 'Draf',
                        'sent'      => 'Dihantar',
                        'accepted'  => 'Diterima',
                        'rejected'  => 'Ditolak',
                        'expired'   => 'Tamat Tempoh',
                        'cancelled' => 'Dibatal',
                        'converted' => 'Ditukar ke Invois',
                    ]),
            ])
            ->actions([
                ViewAction::make()
                    ->url(fn ($record) => static::getUrl('view', ['record' => $record])),

                EditAction::make()
                    ->url(fn ($record) => static::getUrl('edit', ['record' => $record]))
                    ->visible(fn ($record) => $record->status === 'draft'),

                DeleteAction::make()
                    ->visible(fn ($record) => $record->status === 'draft'),
            ])
            ->defaultSort('quotation_date', 'desc');
    }

    // =========================================================================
    // PAGES
    // =========================================================================
    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListQuotations::route('/'),
            'create' => Pages\CreateQuotation::route('/create'),
            'view'   => Pages\ViewQuotation::route('/{record}'),
            'edit'   => Pages\EditQuotation::route('/{record}/edit'),
        ];
    }
}