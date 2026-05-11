<?php

namespace App\Filament\Resources\Companies\Schemas;

use App\Models\Plan;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Company Info')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('registration_number')
                            ->label('SSM No')
                            ->nullable(),
                        TextInput::make('tax_number')
                            ->label('TIN')
                            ->nullable(),
                        TextInput::make('sst_number')
                            ->label('SST No')
                            ->nullable(),
                        TextInput::make('email')->email()->nullable(),
                        TextInput::make('phone')->nullable(),
                    ]),

                Section::make('Plan & Status')
                    ->columns(2)
                    ->schema([
                        Select::make('plan_id')
                            ->label('Current Plan')
                            ->options(fn () => Plan::where('is_active', true)
                                ->orderBy('sort_order')
                                ->pluck('name', 'id'))
                            ->nullable(),
                        Select::make('status')
                            ->options([
                                'draft'     => 'Draft',
                                'active'    => 'Active',
                                'suspended' => 'Suspended',
                            ])
                            ->required(),
                        DateTimePicker::make('onboarding_completed_at')
                            ->label('Onboarding Completed At')
                            ->nullable(),
                    ]),
            ]);
    }
}
