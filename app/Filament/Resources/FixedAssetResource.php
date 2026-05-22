<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FixedAssetResource\Pages;
use App\Models\FixedAsset;
use App\Models\AccountingPeriod;
use App\Services\FixedAssetService;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\EditAction as HeaderEditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;

class FixedAssetResource extends Resource
{
    protected static ?string $model = FixedAsset::class;

    public static function getNavigationGroup(): string
    {
        return 'Aset Tetap';
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-building-office';
    }

    public static function getNavigationLabel(): string
    {
        return 'Senarai Aset';
    }

    public static function getNavigationSort(): int
    {
        return 1;
    }

    public static function getModelLabel(): string
    {
        return 'Aset Tetap';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Aset Tetap';
    }

    // -------------------------------------------------------------------------
    // FORM
    // -------------------------------------------------------------------------
    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Maklumat Aset')
                ->columns(3)
                ->schema([
                    TextInput::make('asset_no')
                        ->label('No. Aset')
                        ->disabled()
                        ->placeholder('Auto-generate (FA-2026-00001)'),

                    TextInput::make('name')
                        ->label('Nama Aset')
                        ->required()
                        ->columnSpan(2)
                        ->placeholder('e.g. Kereta Toyota Hilux 2.4G'),

                    Textarea::make('description')
                        ->label('Penerangan')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),

            Section::make('Kategori & Susut Nilai')
                ->columns(3)
                ->schema([
                    Select::make('category_id')
                        ->label('Kategori')
                        ->relationship('category', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state) {
                                $cat = \App\Models\AssetCategory::find($state);
                                if ($cat) {
                                    $set('useful_life_years', $cat->useful_life_years);
                                    $set('depreciation_method', $cat->depreciation_method);
                                }
                            }
                        }),

                    Select::make('depreciation_method')
                        ->label('Kaedah Susut Nilai')
                        ->options([
                            'straight_line'    => 'Garis Lurus',
                            'reducing_balance' => 'Baki Berkurangan',
                        ])
                        ->required()
                        ->default('straight_line'),

                    TextInput::make('useful_life_years')
                        ->label('Jangka Hayat (Tahun)')
                        ->numeric()
                        ->required()
                        ->default(5)
                        ->suffix('tahun'),
                ]),

            Section::make('Maklumat Pembelian')
                ->columns(3)
                ->schema([
                    DatePicker::make('purchase_date')
                        ->label('Tarikh Beli')
                        ->required()
                        ->default(now()),

                    TextInput::make('purchase_amount')
                        ->label('Kos Pembelian (RM)')
                        ->numeric()
                        ->required()
                        ->prefix('RM')
                        ->reactive(),

                    TextInput::make('salvage_value')
                        ->label('Nilai Sisa (RM)')
                        ->numeric()
                        ->default(0)
                        ->prefix('RM'),

                    Select::make('vendor_id')
                        ->label('Pembekal')
                        ->relationship('vendor', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable(),

                    TextInput::make('vendor_invoice_no')
                        ->label('No. Invois Pembekal')
                        ->placeholder('e.g. INV/2026/001'),

                    Placeholder::make('monthly_dep_preview')
                        ->label('Anggaran Susut Nilai Bulanan')
                        ->content(function ($get) {
                            $cost     = (float) $get('purchase_amount');
                            $salvage  = (float) $get('salvage_value');
                            $life     = (int)   $get('useful_life_years') ?: 1;
                            $method   = $get('depreciation_method') ?? 'straight_line';

                            if (!$cost) return '-';

                            if ($method === 'straight_line') {
                                $monthly = ($cost - $salvage) / $life / 12;
                            } else {
                                $rate    = 1 - pow(($salvage > 0 ? $salvage : 1) / $cost, 1 / $life);
                                $monthly = $cost * $rate / 12;
                            }

                            return 'RM ' . number_format($monthly, 2) . ' / bulan';
                        }),
                ]),

            Section::make('Lokasi & Pengguna')
                ->columns(2)
                ->schema([
                    TextInput::make('location')
                        ->label('Lokasi')
                        ->placeholder('e.g. HQ - Bilik Server, Cawangan KK'),

                    TextInput::make('assigned_to')
                        ->label('Dipegang Oleh')
                        ->placeholder('e.g. Ahmad bin Ali'),
                ]),

            Section::make('Dokumen & Lampiran')
                ->schema([
                    SpatieMediaLibraryFileUpload::make('documents')
                        ->label('Lampiran (Invois, Gambar, Waranti)')
                        ->collection('documents')
                        ->multiple()
                        ->maxFiles(10)
                        ->acceptedFileTypes([
                            'application/pdf',
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ])
                        ->maxSize(5120) // 5MB per file
                        ->columnSpanFull(),
                ]),

            Section::make('Nilai Semasa')
                ->columns(3)
                ->visibleOn('view')
                ->schema([
                    Placeholder::make('current_book_value')
                        ->label('Nilai Buku Semasa')
                        ->content(fn ($record) => $record
                            ? 'RM ' . number_format($record->current_book_value, 2)
                            : '-'),

                    Placeholder::make('accumulated_depreciation')
                        ->label('Susut Nilai Terkumpul')
                        ->content(fn ($record) => $record
                            ? 'RM ' . number_format($record->accumulated_depreciation, 2)
                            : '-'),

                    Placeholder::make('status_label')
                        ->label('Status')
                        ->content(fn ($record) => $record?->status_label ?? '-'),
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
                TextColumn::make('asset_no')
                    ->label('No. Aset')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('name')
                    ->label('Nama Aset')
                    ->searchable()
                    ->limit(30),

                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->badge()
                    ->color('info'),

                TextColumn::make('purchase_amount')
                    ->label('Kos (RM)')
                    ->money('MYR')
                    ->sortable()
                    ->alignRight(),

                TextColumn::make('current_book_value')
                    ->label('Nilai Buku (RM)')
                    ->money('MYR')
                    ->sortable()
                    ->alignRight()
                    ->color(fn ($record) => $record->isFullyDepreciated() ? 'danger' : 'success'),

                TextColumn::make('location')
                    ->label('Lokasi')
                    ->limit(20)
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($record) => $record->status_color)
                    ->formatStateUsing(fn ($record) => $record->status_label),

                TextColumn::make('purchase_date')
                    ->label('Tarikh Beli')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active'      => 'Aktif',
                        'disposed'    => 'Dilupuskan',
                        'written_off' => 'Dihapus Kira',
                    ]),

                SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name'),
            ])
            ->defaultSort('asset_no', 'desc');
    }

    // -------------------------------------------------------------------------
    // PAGES
    // -------------------------------------------------------------------------
    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListFixedAssets::route('/'),
            'create' => Pages\CreateFixedAsset::route('/create'),
            'view'   => Pages\ViewFixedAsset::route('/{record}'),
            'edit'   => Pages\EditFixedAsset::route('/{record}/edit'),
        ];
    }
}
