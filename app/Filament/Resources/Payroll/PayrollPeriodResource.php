<?php

namespace App\Filament\Resources\Payroll;

use App\Filament\Resources\Payroll\Pages\CreatePayrollPeriod;
use App\Filament\Resources\Payroll\Pages\EditPayrollPeriod;
use App\Filament\Resources\Payroll\Pages\ListPayrollPeriods;
use App\Models\PayrollPeriod;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class PayrollPeriodResource extends Resource
{
    protected static ?string $model = PayrollPeriod::class;

    public static function getNavigationGroup(): ?string { return 'Payroll'; }
    public static function getNavigationLabel(): string { return 'Payroll Periods'; }
    public static function getNavigationSort(): ?int { return 2; }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Period Details')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Period Name')
                        ->required()
                        ->placeholder('January 2026')
                        ->maxLength(50),

                    Select::make('status')
                        ->options(['open' => 'Open', 'closed' => 'Closed'])
                        ->default('open')
                        ->required(),

                    TextInput::make('year')
                        ->numeric()
                        ->default(now()->year)
                        ->required(),

                    Select::make('month')
                        ->options(array_combine(range(1, 12), [
                            'January','February','March','April','May','June',
                            'July','August','September','October','November','December'
                        ]))
                        ->default(now()->month)
                        ->required(),

                    DatePicker::make('start_date')->required()->default(now()->startOfMonth()),
                    DatePicker::make('end_date')->required()->default(now()->endOfMonth()),
                    DatePicker::make('payment_date')->nullable()->label('Payment Date'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->sortable()->searchable(),
                TextColumn::make('year')->sortable(),
                TextColumn::make('month')->sortable(),
                TextColumn::make('start_date')->date(),
                TextColumn::make('end_date')->date(),
                TextColumn::make('payment_date')->date()->placeholder('-'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => $state === 'open' ? 'success' : 'gray'),
                TextColumn::make('run.status')
                    ->label('Run Status')
                    ->badge()
                    ->placeholder('No run')
                    ->color(fn (?string $state) => match($state) {
                        'draft'    => 'gray',
                        'approved' => 'warning',
                        'posted'   => 'success',
                        'locked'   => 'info',
                        default    => 'gray',
                    }),
            ])
            ->defaultSort('year', 'desc');
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index'  => ListPayrollPeriods::route('/'),
            'create' => CreatePayrollPeriod::route('/create'),
            'edit'   => EditPayrollPeriod::route('/{record}/edit'),
        ];
    }
}
