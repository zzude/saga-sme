<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;


class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Customer Info')
                    ->columns(2)
                    ->schema([
                        TextInput::make('customer_code')
                            ->label('Customer Code')
                            ->required()
                            ->placeholder('CUST-0001')
                            ->maxLength(20),
                        TextInput::make('name')
                            ->label('Customer Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->nullable(),
                        TextInput::make('phone')
                            ->label('Phone')
                            ->tel()
                            ->nullable(),
                    ]),

                Section::make('Address')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        Textarea::make('address')
                            ->label('Address')
                            ->columnSpanFull()
                            ->nullable(),
                        TextInput::make('city')->nullable(),
                        TextInput::make('state')->nullable(),
                        TextInput::make('postcode')->nullable(),
                        TextInput::make('country')
                            ->default('Malaysia')
                            ->required(),
                    ]),

                Section::make('Business Details')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        TextInput::make('registration_no')
                            ->label('SSM Registration No')
                            ->nullable(),
                        TextInput::make('tax_id')
                            ->label('SST Tax ID')
                            ->nullable(),
                    ]),

                Section::make('Credit Settings')
                    ->columns(2)
                    ->schema([
                        TextInput::make('credit_term_days')
                            ->label('Credit Term (Days)')
                            ->numeric()
                            ->default(30)
                            ->required(),
                        TextInput::make('credit_limit')
                            ->label('Credit Limit (MYR)')
                            ->numeric()
                            ->default(0)
                            ->required(),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->required(),
                    ]),
            ]);
    }
}