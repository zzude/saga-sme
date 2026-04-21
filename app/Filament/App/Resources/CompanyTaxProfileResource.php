<?php

namespace App\Filament\App\Resources;

use App\Models\CompanyTaxProfile;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class CompanyTaxProfileResource extends Resource
{
    protected static ?string $model = CompanyTaxProfile::class;
    public static function getNavigationIcon(): string { return 'heroicon-o-building-office'; }
    public static function getNavigationLabel(): string { return 'SST Profile'; }
    public static function getNavigationSort(): ?int { return 11; }
    public static function getNavigationGroup(): ?string
    {
        return 'Settings';
    }    

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('tax_reg_no')
                ->label('SST Registration No')
                ->placeholder('B16-xxxxxxxx')
                ->nullable(),
            Select::make('tax_type')
                ->label('Tax Type')
                ->options([
                    'sales'   => 'Sales Tax',
                    'service' => 'Service Tax',
                    'both'    => 'Both',
                ])
                ->required(),
            DatePicker::make('effective_date')
                ->label('Effective Date')
                ->nullable(),
            Toggle::make('is_registered')
                ->label('SST Registered')
                ->default(false),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->label('Company'),
                TextColumn::make('tax_reg_no')
                    ->label('SST Reg No')
                    ->placeholder('Not set'),
                TextColumn::make('tax_type')
                    ->badge()
                    ->color(fn($state) => match($state) {
                        'sales'   => 'warning',
                        'service' => 'info',
                        'both'    => 'success',
                        default   => 'gray',
                    }),
                TextColumn::make('effective_date')
                    ->date('d/m/Y')
                    ->placeholder('—'),
                IconColumn::make('is_registered')
                    ->boolean(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => \App\Filament\App\Resources\CompanyTaxProfileResource\Pages\ListCompanyTaxProfiles::route('/'),
            'create' => \App\Filament\App\Resources\CompanyTaxProfileResource\Pages\CreateCompanyTaxProfile::route('/create'),
            'edit'   => \App\Filament\App\Resources\CompanyTaxProfileResource\Pages\EditCompanyTaxProfile::route('/{record}/edit'),
        ];
    }
}