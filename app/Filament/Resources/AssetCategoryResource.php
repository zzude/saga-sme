<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssetCategoryResource\Pages;
use App\Models\AssetCategory;
use App\Models\Account;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;

class AssetCategoryResource extends Resource
{
    protected static ?string $model = AssetCategory::class;

    public static function getNavigationGroup(): string
    {
        return 'Aset Tetap';
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-tag';
    }

    public static function getNavigationLabel(): string
    {
        return 'Kategori Aset';
    }

    public static function getNavigationSort(): int
    {
        return 2;
    }

    public static function getModelLabel(): string
    {
        return 'Kategori Aset';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Kategori Aset';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Maklumat Kategori')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Nama Kategori')
                        ->required()
                        ->placeholder('e.g. Kenderaan, Peralatan Pejabat, Komputer'),

                    Select::make('depreciation_method')
                        ->label('Kaedah Susut Nilai')
                        ->options([
                            'straight_line'    => 'Garis Lurus (Straight Line)',
                            'reducing_balance' => 'Baki Berkurangan (Reducing Balance)',
                        ])
                        ->required()
                        ->default('straight_line'),

                    TextInput::make('useful_life_years')
                        ->label('Jangka Hayat (Tahun)')
                        ->numeric()
                        ->required()
                        ->default(5)
                        ->suffix('tahun'),

                    Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true),
                ]),

            Section::make('Akaun GL')
                ->description('Akaun dalam Carta Akaun yang akan digunakan untuk jurnal aset.')
                ->columns(1)
                ->schema([
                    Select::make('asset_account_id')
                        ->label('Akaun Aset Tetap (DR semasa beli)')
                        ->options(fn () => Account::where('company_id', auth()->user()->company_id)
                            ->where('type', 'asset')
                            ->orderBy('code')
                            ->pluck('name', 'id')
                            ->map(fn ($name, $id) => Account::find($id)?->code . ' - ' . $name)
                        )
                        ->searchable()
                        ->placeholder('Pilih akaun aset...'),

                    Select::make('accumulated_depreciation_account_id')
                        ->label('Akaun Susut Nilai Terkumpul (CR susut nilai)')
                        ->options(fn () => Account::where('company_id', auth()->user()->company_id)
                            ->where('type', 'asset')
                            ->orderBy('code')
                            ->pluck('name', 'id')
                            ->map(fn ($name, $id) => Account::find($id)?->code . ' - ' . $name)
                        )
                        ->searchable()
                        ->placeholder('Pilih akaun susut nilai terkumpul...'),

                    Select::make('depreciation_expense_account_id')
                        ->label('Akaun Belanja Susut Nilai (DR susut nilai)')
                        ->options(fn () => Account::where('company_id', auth()->user()->company_id)
                            ->whereIn('type', ['expense'])
                            ->orderBy('code')
                            ->pluck('name', 'id')
                            ->map(fn ($name, $id) => Account::find($id)?->code . ' - ' . $name)
                        )
                        ->searchable()
                        ->placeholder('Pilih akaun belanja susut nilai...'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('depreciation_method')
                    ->label('Kaedah')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'straight_line'    => 'Garis Lurus',
                        'reducing_balance' => 'Baki Berkurangan',
                        default            => $state,
                    })
                    ->badge()
                    ->color(fn ($state) => $state === 'straight_line' ? 'info' : 'warning'),

                TextColumn::make('useful_life_years')
                    ->label('Jangka Hayat')
                    ->suffix(' tahun')
                    ->sortable(),

                TextColumn::make('assets_count')
                    ->label('Bilangan Aset')
                    ->counts('assets')
                    ->badge()
                    ->color('gray'),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAssetCategories::route('/'),
            'create' => Pages\CreateAssetCategory::route('/create'),
            'edit'   => Pages\EditAssetCategory::route('/{record}/edit'),
        ];
    }
}
