<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\AccountingPeriodResource\Pages;
use App\Models\AccountingPeriod;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AccountingPeriodResource extends Resource
{
    protected static ?string $model = AccountingPeriod::class;
    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-calendar';
    }
    protected static ?string $navigationLabel = 'Accounting Periods';
    public static function getNavigationGroup(): string
    {
        return 'Settings';
    }
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Period Name')
                    ->placeholder('contoh: Jan 2026, FY2026 Q1')
                    ->required()
                    ->maxLength(50),

                DatePicker::make('start_date')
                    ->label('Start Date')
                    ->required()
                    ->native(false),

                DatePicker::make('end_date')
                    ->label('End Date')
                    ->required()
                    ->native(false)
                    ->afterOrEqual('start_date'),

                Select::make('status')
                    ->label('Status')
                    ->options([
                        'open'   => 'Open',
                        'closed' => 'Closed',
                        'locked' => 'Locked',
                    ])
                    ->default('open')
                    ->required()
                    ->native(false),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Period')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('start_date')
                    ->label('Start')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('end_date')
                    ->label('End')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'open'   => 'success',
                        'closed' => 'warning',
                        'locked' => 'danger',
                    }),

                TextColumn::make('closedBy.name')
                    ->label('Closed By')
                    ->placeholder('—'),

                TextColumn::make('closed_at')
                    ->label('Closed At')
                    ->dateTime('d M Y H:i')
                    ->placeholder('—'),
            ])
            ->defaultSort('start_date', 'desc')
            ->actions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAccountingPeriods::route('/'),
            'create' => Pages\CreateAccountingPeriod::route('/create'),
            'edit'   => Pages\EditAccountingPeriod::route('/{record}/edit'),
        ];
    }
}