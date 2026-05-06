<?php

namespace App\Filament\Resources\Payroll;

use App\Filament\Resources\Payroll\Pages\CreatePayrollRun;
use App\Filament\Resources\Payroll\Pages\ListPayrollRuns;
use App\Filament\Resources\Payroll\Pages\ViewPayrollRun;
use App\Models\AccountingPeriod;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Services\PayrollService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class PayrollRunResource extends Resource
{
    protected static ?string $model = PayrollRun::class;

    public static function getNavigationGroup(): ?string { return 'Payroll'; }
    public static function getNavigationIcon(): string|\BackedEnum|null { return Heroicon::OutlinedCpuChip; }
    public static function getNavigationLabel(): string { return 'Payroll Runs'; }
    public static function getNavigationSort(): ?int { return 3; }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            \Filament\Schemas\Components\Section::make('Payroll Run')
                ->columns(2)
                ->schema([
                    Select::make('payroll_period_id')
                        ->label('Payroll Period')
                        ->options(fn () => PayrollPeriod::where('company_id', Auth::user()->company_id)
                            ->where('status', 'open')
                            ->orderByDesc('year')
                            ->orderByDesc('month')
                            ->get()
                            ->mapWithKeys(fn ($p) => [$p->id => $p->name])
                        )
                        ->required(),

                    Select::make('period_id')
                        ->label('Accounting Period')
                        ->options(fn () => AccountingPeriod::where('company_id', Auth::user()->company_id)
                            ->orderByDesc('start_date')
                            ->pluck('name', 'id'))
                        ->required(),

                    TextInput::make('reference_no')
                        ->label('Reference No')
                        ->default(fn () => 'PR-' . now()->format('Y-m'))
                        ->required()
                        ->maxLength(30),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference_no')->sortable()->searchable(),
                TextColumn::make('payrollPeriod.name')->label('Period'),
                TextColumn::make('total_gross')->money('MYR')->alignRight()->label('Gross'),
                TextColumn::make('total_net_salary')->money('MYR')->alignRight()->label('Net'),
                TextColumn::make('total_employer_cost')->money('MYR')->alignRight()->label('Total Cost'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match($state) {
                        'draft'    => 'gray',
                        'approved' => 'warning',
                        'posted'   => 'success',
                        'locked'   => 'info',
                        default    => 'gray',
                    }),
                TextColumn::make('posted_at')->dateTime()->placeholder('-'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index'  => ListPayrollRuns::route('/'),
            'create' => CreatePayrollRun::route('/create'),
            'view'   => ViewPayrollRun::route('/{record}'),
        ];
    }
}
