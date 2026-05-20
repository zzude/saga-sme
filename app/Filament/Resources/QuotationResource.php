<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuotationResource\Pages;
use App\Models\Quotation;
use App\Models\Customer;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Actions\ViewAction as HeaderViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Placeholder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class QuotationResource extends Resource
{
    protected static ?string $model = Quotation::class;

    public static function getNavigationGroup(): string
    {
        return 'Jualan';
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-document-text';
    }

    public static function getNavigationLabel(): string
    {
        return 'Sebut Harga';
    }

    public static function getNavigationSort(): int
    {
        return 1; // before Invoice
    }

    public static function getModelLabel(): string
    {
        return 'Sebut Harga';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Sebut Harga';
    }

    // -------------------------------------------------------------------------
    // FORM
    // -------------------------------------------------------------------------
    public static function form(Schema $schema): Schema
    {
        return $schema->components([

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
                        ->placeholder('e.g. Supply of IT Equipment, Kerja Pembinaan Pagar...'),
                ]),

            Section::make('Maklumat Pelanggan')
                ->columns(2)
                ->schema([
                    Select::make('customer_id')
                        ->label('Pelanggan')
                        ->relationship('customer', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state) {
                                $customer = Customer::find($state);
                                if ($customer) {
                                    $set('customer_name', $customer->name);
                                    $set('customer_address', $customer->address);
                                }
                            }
                        }),

                    TextInput::make('attention_to')
                        ->label('Perhatian (Attn)')
                        ->placeholder('Nama pegawai / wakil'),

                    TextInput::make('customer_name')
                        ->label('Nama Syarikat')
                        ->required(),

                    Textarea::make('customer_address')
                        ->label('Alamat')
                        ->rows(3),
                ]),

            Section::make('Senarai Item')
                ->schema([
                    Repeater::make('items')
                        ->relationship()
                        ->label('')
                        ->schema([
                            Grid::make(12)->schema([
                                TextInput::make('line_no')
                                    ->label('No.')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->columnSpan(1),

                                TextInput::make('description')
                                    ->label('Penerangan')
                                    ->required()
                                    ->columnSpan(4),

                                TextInput::make('detail')
                                    ->label('Spesifikasi')
                                    ->columnSpan(3),

                                TextInput::make('unit_of_measure')
                                    ->label('Unit')
                                    ->default('unit')
                                    ->columnSpan(1),

                                TextInput::make('quantity')
                                    ->label('Kuantiti')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->reactive()
                                    ->columnSpan(1),

                                TextInput::make('unit_price')
                                    ->label('Harga Unit (RM)')
                                    ->numeric()
                                    ->required()
                                    ->reactive()
                                    ->columnSpan(2),

                                TextInput::make('discount_percent')
                                    ->label('Diskaun %')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('%')
                                    ->reactive()
                                    ->columnSpan(1),

                                Toggle::make('is_sst_applicable')
                                    ->label('SST')
                                    ->default(false)
                                    ->reactive()
                                    ->columnSpan(1),

                                TextInput::make('sst_rate')
                                    ->label('Kadar SST %')
                                    ->numeric()
                                    ->default(6)
                                    ->suffix('%')
                                    ->hidden(fn ($get) => !$get('is_sst_applicable'))
                                    ->columnSpan(1),

                                Placeholder::make('total_amount')
                                    ->label('Jumlah (RM)')
                                    ->content(function ($get) {
                                        $qty      = (float) $get('quantity');
                                        $price    = (float) $get('unit_price');
                                        $disc     = (float) $get('discount_percent');
                                        $sstOn    = (bool)  $get('is_sst_applicable');
                                        $sstRate  = (float) $get('sst_rate') ?: 6;

                                        $gross    = $qty * $price;
                                        $discAmt  = $gross * ($disc / 100);
                                        $net      = $gross - $discAmt;
                                        $sst      = $sstOn ? $net * ($sstRate / 100) : 0;
                                        $total    = $net + $sst;

                                        return new HtmlString(
                                            '<span class="font-semibold text-primary-600">RM ' . number_format($total, 2) . '</span>'
                                        );
                                    })
                                    ->columnSpan(2),
                            ]),
                        ])
                        ->orderColumn('line_no')
                        ->addActionLabel('+ Tambah Item')
                        ->reorderable()
                        ->collapsible()
                        ->defaultItems(1),
                ]),

            Section::make('Ringkasan Jumlah')
                ->columns(3)
                ->schema([
                    Toggle::make('sst_applicable')
                        ->label('SST Dikenakan?')
                        ->default(false)
                        ->reactive(),

                    TextInput::make('sst_rate')
                        ->label('Kadar SST %')
                        ->numeric()
                        ->default(6)
                        ->suffix('%')
                        ->hidden(fn ($get) => !$get('sst_applicable')),

                    TextInput::make('payment_terms_days')
                        ->label('Terma Pembayaran (hari)')
                        ->numeric()
                        ->default(30)
                        ->suffix('hari'),
                ]),

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

    // -------------------------------------------------------------------------
    // TABLE
    // -------------------------------------------------------------------------
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('quotation_number')
                    ->label('No. Sebut Harga')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

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
                    ->alignRight(),

                TextColumn::make('valid_until')
                    ->label('Sah Hingga')
                    ->date('d/m/Y')
                    ->color(fn ($record) => $record->is_expired ? 'danger' : null),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($record) => $record->status_color)
                    ->formatStateUsing(fn ($record) => $record->status_label),

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
                        'converted' => 'Ditukar ke Invois',
                    ]),
            ])
            ->defaultSort('quotation_date', 'desc');
    }

    // -------------------------------------------------------------------------
    // PAGES
    // -------------------------------------------------------------------------
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
