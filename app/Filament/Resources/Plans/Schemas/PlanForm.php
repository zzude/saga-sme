<?php

namespace App\Filament\Resources\Plans\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Plan Details')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(50)
                            ->placeholder('Pro'),
                        TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->placeholder('pro'),
                        TextInput::make('price_monthly')
                            ->label('Price (MYR/month)')
                            ->numeric()
                            ->default(0)
                            ->prefix('RM')
                            ->required(),
                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->required(),
                    ]),

                Section::make('Limits')
                    ->columns(3)
                    ->description('-1 = Unlimited')
                    ->schema([
                        TextInput::make('max_users')
                            ->label('Max Users')
                            ->numeric()
                            ->default(2)
                            ->required(),
                        TextInput::make('max_invoices_per_month')
                            ->label('Max Invoices/Month')
                            ->numeric()
                            ->default(20)
                            ->required(),
                        TextInput::make('max_customers')
                            ->label('Max Customers')
                            ->numeric()
                            ->default(50)
                            ->required(),
                    ]),

                Section::make('Features')
                    ->columns(3)
                    ->schema([
                        Toggle::make('has_einvoice')
                            ->label('e-Invoice (MyInvois)')
                            ->default(false),
                        Toggle::make('has_multicurrency')
                            ->label('Multi-Currency')
                            ->default(false),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ]),
            ]);
    }
}
