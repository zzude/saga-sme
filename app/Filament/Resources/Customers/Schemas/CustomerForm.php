<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

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

                // NEW — e-Invoice / LHDN Section
                Section::make('e-Invoice / LHDN Info')
                    ->columns(2)
                    ->collapsed()
                    ->description('Required for MyInvois e-Invoice submission to LHDN.')
                    ->schema([
                        Toggle::make('is_individual')
                            ->label('Individual / B2C Customer')
                            ->helperText('ON = Consolidated e-Invoice (B2C). OFF = Submit per transaction (B2B).')
                            ->default(true)
                            ->columnSpanFull(),
                        TextInput::make('tin')
                            ->label('TIN (Tax Identification Number)')
                            ->placeholder('C12345678901')
                            ->helperText('LHDN Tax ID. Leave empty for walk-in customers.')
                            ->nullable(),
                        Select::make('id_type')
                            ->label('ID Type')
                            ->options([
                                'NRIC'     => 'NRIC (MyKad)',
                                'BRN'      => 'BRN (SSM Registration)',
                                'PASSPORT' => 'Passport',
                                'ARMY'     => 'Army ID',
                            ])
                            ->nullable(),
                        TextInput::make('id_value')
                            ->label('ID Number')
                            ->placeholder('e.g. 901231-12-1234 or 202301012345')
                            ->nullable(),
                        TextInput::make('sst_registration_no')
                            ->label('SST Registration No')
                            ->placeholder('W10-1234-12345678')
                            ->nullable(),
                        TextInput::make('msic_code')
                            ->label('MSIC Code')
                            ->placeholder('e.g. 62010')
                            ->helperText('Malaysia Standard Industrial Classification code.')
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