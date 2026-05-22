<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ItemResource\Pages;
use App\Models\Item;
use App\Models\Account;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;

class ItemResource extends Resource
{
    protected static ?string $model = Item::class;

    public static function getNavigationGroup(): string
    {
        return 'Inventori';
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-cube';
    }

    public static function getNavigationLabel(): string
    {
        return 'Katalog Item';
    }

    public static function getNavigationSort(): int
    {
        return 1;
    }

    public static function getModelLabel(): string
    {
        return 'Item';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Katalog Item';
    }

    // -------------------------------------------------------------------------
    // FORM
    // -------------------------------------------------------------------------
    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Maklumat Item')
                ->columns(3)
                ->schema([
                    TextInput::make('code')
                        ->label('Kod Item')
                        ->placeholder('e.g. ITM-001')
                        ->unique(ignoreRecord: true),

                    TextInput::make('name')
                        ->label('Nama Item')
                        ->required()
                        ->columnSpan(2)
                        ->placeholder('e.g. Laptop Dell Inspiron 15, Kerja Konsultansi'),

                    Select::make('type')
                        ->label('Jenis')
                        ->options([
                            'product' => 'Produk (Barang Fizikal)',
                            'service' => 'Perkhidmatan',
                            'bundle'  => 'Bundle',
                        ])
                        ->required()
                        ->default('product')
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state === 'service') {
                                $set('track_inventory', false);
                            } else {
                                $set('track_inventory', true);
                            }
                        }),

                    TextInput::make('category')
                        ->label('Kategori')
                        ->placeholder('e.g. Elektronik, Alat Tulis, IT Services'),

                    TextInput::make('unit_of_measure')
                        ->label('Unit Ukuran')
                        ->default('unit')
                        ->placeholder('unit, kg, liter, jam, set'),

                    Textarea::make('description')
                        ->label('Penerangan')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),

            Section::make('Harga')
                ->columns(3)
                ->schema([
                    TextInput::make('selling_price')
                        ->label('Harga Jualan (RM)')
                        ->numeric()
                        ->required()
                        ->default(0)
                        ->prefix('RM'),

                    TextInput::make('cost_price')
                        ->label('Harga Kos (RM)')
                        ->numeric()
                        ->default(0)
                        ->prefix('RM'),

                    Placeholder::make('margin_preview')
                        ->label('Margin Kasar')
                        ->content(function ($get) {
                            $sell = (float) $get('selling_price');
                            $cost = (float) $get('cost_price');
                            if (!$sell) return '-';
                            $margin = $sell - $cost;
                            $pct    = $sell > 0 ? round(($margin / $sell) * 100, 1) : 0;
                            return 'RM ' . number_format($margin, 2) . ' (' . $pct . '%)';
                        }),

                    Toggle::make('is_sst_applicable')
                        ->label('SST Dikenakan?')
                        ->default(false)
                        ->reactive(),

                    TextInput::make('sst_rate')
                        ->label('Kadar SST %')
                        ->numeric()
                        ->default(8)
                        ->suffix('%')
                        ->hidden(fn ($get) => !$get('is_sst_applicable')),
                ]),

            Section::make('Inventori')
                ->columns(3)
                ->schema([
                    Toggle::make('track_inventory')
                        ->label('Track Stok?')
                        ->default(true)
                        ->reactive()
                        ->helperText('Nyahaktif untuk perkhidmatan atau item tanpa stok.'),

                    TextInput::make('current_stock')
                        ->label('Stok Semasa')
                        ->numeric()
                        ->default(0)
                        ->suffix('unit')
                        ->hidden(fn ($get) => !$get('track_inventory'))
                        ->helperText('Stok pembuka. Selepas ini gunakan Pelarasan Stok.'),

                    TextInput::make('reorder_level')
                        ->label('Paras Reorder')
                        ->numeric()
                        ->default(0)
                        ->suffix('unit')
                        ->hidden(fn ($get) => !$get('track_inventory'))
                        ->helperText('Amaran akan muncul bila stok ≤ paras ini.'),
                ]),

            Section::make('Akaun GL')
                ->columns(2)
                ->schema([
                    Select::make('income_account_id')
                        ->label('Akaun Pendapatan (default)')
                        ->options(fn () => Account::where('company_id', auth()->user()->company_id)
                            ->where('type', 'income')
                            ->orderBy('code')
                            ->get()
                            ->mapWithKeys(fn ($a) => [$a->id => $a->code . ' — ' . $a->name])
                        )
                        ->searchable()
                        ->nullable()
                        ->placeholder('Pilih akaun pendapatan...'),

                    Select::make('expense_account_id')
                        ->label('Akaun Kos (COGS)')
                        ->options(fn () => Account::where('company_id', auth()->user()->company_id)
                            ->whereIn('type', ['expense', 'cost_of_sales'])
                            ->orderBy('code')
                            ->get()
                            ->mapWithKeys(fn ($a) => [$a->id => $a->code . ' — ' . $a->name])
                        )
                        ->searchable()
                        ->nullable()
                        ->placeholder('Pilih akaun COGS...'),
                ]),

            Section::make('Status')
                ->columns(1)
                ->schema([
                    Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true),
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
                TextColumn::make('code')
                    ->label('Kod')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->placeholder('-'),

                TextColumn::make('name')
                    ->label('Nama Item')
                    ->searchable()
                    ->sortable()
                    ->limit(35),

                TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn ($record) => $record->type_color)
                    ->formatStateUsing(fn ($record) => $record->type_label),

                TextColumn::make('category')
                    ->label('Kategori')
                    ->badge()
                    ->color('gray')
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('selling_price')
                    ->label('Harga Jual (RM)')
                    ->money('MYR')
                    ->sortable()
                    ->alignRight(),

                TextColumn::make('current_stock')
                    ->label('Stok')
                    ->formatStateUsing(function ($record) {
                        if (!$record->track_inventory) return '—';
                        $stock = number_format($record->current_stock, 0);
                        return $stock . ' ' . $record->unit_of_measure;
                    })
                    ->color(function ($record) {
                        if (!$record->track_inventory) return 'gray';
                        if ($record->isOutOfStock()) return 'danger';
                        if ($record->isLowStock()) return 'warning';
                        return 'success';
                    })
                    ->alignRight(),

                TextColumn::make('unit_of_measure')
                    ->label('Unit')
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_sst_applicable')
                    ->label('SST')
                    ->boolean()
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Jenis')
                    ->options([
                        'product' => 'Produk',
                        'service' => 'Perkhidmatan',
                        'bundle'  => 'Bundle',
                    ]),

                TernaryFilter::make('is_active')
                    ->label('Status'),

                TernaryFilter::make('track_inventory')
                    ->label('Track Inventori'),
            ])
            ->defaultSort('name');
    }

    // -------------------------------------------------------------------------
    // PAGES
    // -------------------------------------------------------------------------
    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListItems::route('/'),
            'create' => Pages\CreateItem::route('/create'),
            'view'   => Pages\ViewItem::route('/{record}'),
            'edit'   => Pages\EditItem::route('/{record}/edit'),
        ];
    }
}