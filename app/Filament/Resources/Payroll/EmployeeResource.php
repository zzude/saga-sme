<?php

namespace App\Filament\Resources\Payroll;

use App\Filament\Resources\Payroll\Pages\CreateEmployee;
use App\Filament\Resources\Payroll\Pages\EditEmployee;
use App\Filament\Resources\Payroll\Pages\ListEmployees;
use App\Filament\Resources\Payroll\Pages\ViewEmployee;
use App\Models\Employee;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;
    
    

    public static function getNavigationGroup(): ?string { return 'Payroll'; }
    public static function getNavigationIcon(): string|\BackedEnum|null { return Heroicon::OutlinedUsers; }
    public static function getNavigationSort(): ?int { return 1; }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Personal Info')
                ->columns(2)
                ->schema([
                    TextInput::make('employee_no')
                        ->label('Employee No')
                        ->required()
                        ->default(function () {
                            $companyId = Auth::user()->company_id;
                            $latest = Employee::where('company_id', $companyId)
                                ->orderByDesc('id')->first();
                            $next = $latest ? (int) substr($latest->employee_no, -4) + 1 : 1;
                            return 'EMP-' . str_pad($next, 4, '0', STR_PAD_LEFT);
                        })
                        ->maxLength(20),

                    TextInput::make('name')->required()->maxLength(100),
                    TextInput::make('ic_no')->label('IC / Passport No')->nullable()->maxLength(20),
                    TextInput::make('email')->email()->nullable(),
                    TextInput::make('phone')->nullable()->maxLength(20),

                    Select::make('gender')
                        ->options(['male' => 'Male', 'female' => 'Female'])
                        ->nullable(),

                    DatePicker::make('date_of_birth')->nullable(),
                    DatePicker::make('date_joined')->required()->default(now()),
                    DatePicker::make('date_resigned')->nullable(),
                ]),

            Section::make('Employment Details')
                ->columns(2)
                ->schema([
                    TextInput::make('position')->nullable()->maxLength(100),
                    TextInput::make('department')->nullable()->maxLength(100),

                    Select::make('employment_type')
                        ->options([
                            'full_time' => 'Full Time',
                            'part_time' => 'Part Time',
                            'contract'  => 'Contract',
                        ])
                        ->default('full_time')
                        ->required(),

                    TextInput::make('basic_salary')
                        ->label('Basic Salary (MYR)')
                        ->numeric()
                        ->default(0)
                        ->required()
                        ->extraInputAttributes(['style' => 'text-align: right;']),

                    Toggle::make('is_active')->default(true)->columnSpanFull(),
                ]),

            Section::make('Statutory Info')
                ->columns(2)
                ->schema([
                    TextInput::make('epf_no')->label('EPF No')->nullable()->maxLength(20),
                    TextInput::make('socso_no')->label('SOCSO No')->nullable()->maxLength(20),
                    TextInput::make('income_tax_no')->label('Income Tax No')->nullable()->maxLength(20),

                    Select::make('marital_status')
                        ->options([
                            'single'                      => 'Single',
                            'married_spouse_working'      => 'Married (Spouse Working)',
                            'married_spouse_not_working'  => 'Married (Spouse Not Working)',
                        ])
                        ->default('single')
                        ->required(),

                    TextInput::make('children_count')
                        ->label('No. of Children')
                        ->numeric()
                        ->default(0)
                        ->minValue(0),
                ]),

            Section::make('Bank Info')
                ->columns(2)
                ->schema([
                    TextInput::make('bank_name')->nullable()->maxLength(100),
                    TextInput::make('bank_account_no')->nullable()->maxLength(30),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee_no')->label('No.')->sortable()->searchable(),
                TextColumn::make('name')->sortable()->searchable(),
                TextColumn::make('position')->placeholder('-'),
                TextColumn::make('department')->placeholder('-'),
                TextColumn::make('basic_salary')
                    ->label('Basic Salary')
                    ->money('MYR')
                    ->alignRight(),
                TextColumn::make('employment_type')
                    ->badge()
                    ->color(fn (string $state) => match($state) {
                        'full_time' => 'success',
                        'part_time' => 'warning',
                        'contract'  => 'info',
                        default     => 'gray',
                    }),
                IconColumn::make('is_active')->boolean()->label('Active'),
                TextColumn::make('date_joined')->date()->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Active Only'),
                SelectFilter::make('employment_type')
                    ->options([
                        'full_time' => 'Full Time',
                        'part_time' => 'Part Time',
                        'contract'  => 'Contract',
                    ]),
                SelectFilter::make('department')->options(
                    fn () => Employee::where('company_id', Auth::user()->company_id)
                        ->whereNotNull('department')
                        ->distinct()
                        ->pluck('department', 'department')
                ),
            ])
            ->defaultSort('employee_no');
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index'  => ListEmployees::route('/'),
            'create' => CreateEmployee::route('/create'),
            'view'   => ViewEmployee::route('/{record}'),
            'edit'   => EditEmployee::route('/{record}/edit'),
        ];
    }
}
