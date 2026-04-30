<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CurrencyResource\Pages;
use App\Models\Currency;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class CurrencyResource extends Resource
{
    protected static ?string $model = Currency::class;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-currency-dollar';
    }

    public static function getNavigationGroup(): string|\BackedEnum|null
    {
        return 'Settings';
    }

    public static function getNavigationLabel(): string
    {
        return 'Currencies';
    }

    public static function getNavigationSort(): ?int
    {
        return 10;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Currency Details')
                ->columns(2)
                ->components([
                    TextInput::make('code')
                        ->label('Currency Code')
                        ->maxLength(3)
                        ->required()
                        ->disabled(fn ($record) => $record !== null) // immutable after create
                        ->helperText('3-letter ISO code, e.g. USD, SGD'),

                    TextInput::make('name')
                        ->label('Currency Name')
                        ->required()
                        ->maxLength(50),

                    TextInput::make('symbol')
                        ->label('Symbol')
                        ->required()
                        ->maxLength(10),

                    TextInput::make('decimal_places')
                        ->label('Decimal Places')
                        ->numeric()
                        ->required()
                        ->default(2)
                        ->minValue(0)
                        ->maxValue(4),

                    Toggle::make('is_active')
                        ->label('Active')
                        ->helperText('Inactive currencies are hidden from transaction forms')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->badge()
                    ->color(fn ($record) => $record->code === 'MYR' ? 'success' : 'info')
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Currency')
                    ->searchable(),

                TextColumn::make('symbol')
                    ->label('Symbol'),

                TextColumn::make('decimal_places')
                    ->label('Decimals')
                    ->alignCenter(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->alignCenter(),
            ])
            ->defaultSort('code')
            ->actions([
                EditAction::make()
                    ->hidden(fn ($record) => $record->code === 'MYR'), // MYR is immutable
            ])
            ->bulkActions([])
            ->paginated(false); // small table, no pagination needed
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCurrencies::route('/'),
            'create' => Pages\CreateCurrency::route('/create'),
            'edit'   => Pages\EditCurrency::route('/{record}/edit'),
        ];
    }
}