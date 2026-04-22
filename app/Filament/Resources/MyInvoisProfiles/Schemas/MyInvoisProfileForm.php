<?php

namespace App\Filament\Resources\MyInvoisProfiles\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MyInvoisProfileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make("MyInvois Configuration")
                    ->description("LHDN MyInvois API credentials dan settings")
                    ->columns(2)
                    ->components([
                        Select::make("environment")
                            ->options(["sandbox" => "Sandbox (Testing)", "production" => "Production"])
                            ->default("sandbox")
                            ->required(),
                        Select::make("mode")
                            ->options(["taxpayer" => "Taxpayer", "intermediary" => "Intermediary"])
                            ->default("taxpayer")
                            ->required(),
                        TextInput::make("tin")
                            ->label("TIN (Tax Identification Number)")
                            ->placeholder("C12345678900")
                            ->columnSpanFull(),
                        TextInput::make("client_id")
                            ->label("Client ID")
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make("client_secret")
                            ->label("Client Secret")
                            ->password()
                            ->revealable()
                            ->columnSpanFull(),
                        TextInput::make("branch_code")
                            ->label("Branch Code")
                            ->placeholder("00000"),
                        Toggle::make("is_active")
                            ->label("Active")
                            ->default(false),
                    ]),
            ]);
    }
}
