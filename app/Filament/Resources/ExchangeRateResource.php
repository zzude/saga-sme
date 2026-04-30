<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExchangeRateResource\Pages;
use App\Models\ExchangeRate;
use App\Services\ExchangeRateService;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Filament\Actions\EditAction;

class ExchangeRateResource extends Resource
{
    protected static ?string $model = ExchangeRate::class;

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
        return 11;
    }    

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Rate Details')
                ->columns(2)
                ->components([
                    DatePicker::make('rate_date')
                        ->label('Date')
                        ->required()
                        ->default(now()),

                    Select::make('from_currency')
                        ->label('Currency')
                        ->options(
                            \App\Models\Currency::where('is_active', true)
                                ->where('code', '!=', 'MYR')
                                ->pluck('name', 'code')
                                ->map(fn ($name, $code) => "{$code} — {$name}")
                        )
                        ->required(),

                    TextInput::make('rate')
                        ->label('Rate (1 unit → MYR)')
                        ->numeric()
                        ->required()
                        ->step(0.00000001)
                        ->helperText('e.g. 1 USD = 4.7200 MYR'),

                    Placeholder::make('to_currency')
                        ->label('To Currency')
                        ->content('MYR (always)'),

                    Textarea::make('override_reason')
                        ->label('Reason for Manual Override')
                        ->placeholder('e.g. Bank-agreed rate for contract XYZ')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('rate_date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('from_currency')
                    ->label('Currency')
                    ->badge()
                    ->color('info'),

                TextColumn::make('rate')
                    ->label('Rate (→ MYR)')
                    ->formatStateUsing(fn ($state) => number_format($state, 6))
                    ->sortable(),

                TextColumn::make('source')
                    ->label('Source')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'API'    => 'success',
                        'MANUAL' => 'warning',
                        default  => 'gray',
                    }),

                TextColumn::make('is_locked')
                    ->label('Locked')
                    ->formatStateUsing(fn ($state) => $state ? '🔒 Yes' : '—')
                    ->alignCenter(),

                TextColumn::make('fetched_at')
                    ->label('Fetched At')
                    ->dateTime('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('rate_date', 'desc')
            ->filters([
                SelectFilter::make('from_currency')
                    ->label('Currency')
                    ->options(['USD' => 'USD', 'SGD' => 'SGD']),

                SelectFilter::make('source')
                    ->label('Source')
                    ->options(['API' => 'API (Auto)', 'MANUAL' => 'Manual']),
            ])
            ->actions([
                EditAction::make()
                    ->label('Override')
                    ->icon('heroicon-o-pencil-square')
                    ->hidden(fn ($record) => $record->is_locked),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListExchangeRates::route('/'),
            'create' => Pages\CreateExchangeRate::route('/create'),
            'edit'   => Pages\EditExchangeRate::route('/{record}/edit'),
        ];
    }
}