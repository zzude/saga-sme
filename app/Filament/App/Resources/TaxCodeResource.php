<?php

namespace App\Filament\App\Resources;

use App\Models\TaxCode;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class TaxCodeResource extends Resource
{
    protected static ?string $model = TaxCode::class;
    public static function getNavigationIcon(): string { return 'heroicon-o-receipt-percent'; }
    public static function getNavigationLabel(): string { return 'Tax Codes'; }
    public static function getNavigationSort(): ?int { return 10; }
    public static function getNavigationGroup(): ?string
    {
        return 'Settings';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')
                ->required()
                ->maxLength(10)
                ->extraAttributes(['style' => 'text-transform:uppercase']),
            TextInput::make('description')
                ->required()
                ->columnSpan(2),
            Select::make('tax_type')
                ->options([
                    'sales'        => 'Sales Tax',
                    'service'      => 'Service Tax',
                    'exempt'       => 'Exempt',
                    'out_of_scope' => 'Out of Scope',
                ])
                ->required(),
            TextInput::make('rate')
                ->numeric()
                ->suffix('%')
                ->required()
                ->default(0),
            DatePicker::make('effective_from')
                ->nullable(),
            DatePicker::make('effective_to')
                ->nullable(),
            Toggle::make('is_active')
                ->default(true),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('description')
                    ->searchable(),
                TextColumn::make('tax_type')
                    ->badge()
                    ->color(fn($state) => match($state) {
                        'sales'        => 'warning',
                        'service'      => 'info',
                        'exempt'       => 'success',
                        'out_of_scope' => 'gray',
                        default        => 'gray',
                    }),
                TextColumn::make('rate')
                    ->suffix('%')
                    ->sortable(),
                TextColumn::make('effective_from')
                    ->date('d/m/Y')
                    ->placeholder('—'),
                TextColumn::make('effective_to')
                    ->date('d/m/Y')
                    ->placeholder('—'),
                IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->defaultSort('code')
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);          
    }

    public static function getPages(): array
    {
        return [
            'index'  => \App\Filament\App\Resources\TaxCodeResource\Pages\ListTaxCodes::route('/'),
            'create' => \App\Filament\App\Resources\TaxCodeResource\Pages\CreateTaxCode::route('/create'),
            'edit'   => \App\Filament\App\Resources\TaxCodeResource\Pages\EditTaxCode::route('/{record}/edit'),
        ];
    }
}