<?php

namespace App\Filament\Resources\StaffLoans;

use App\Filament\Resources\StaffLoans\Pages\CreateStaffLoan;
use App\Filament\Resources\StaffLoans\Pages\EditStaffLoan;
use App\Filament\Resources\StaffLoans\Pages\ListStaffLoans;
use App\Filament\Resources\StaffLoans\Pages\ViewStaffLoan;
use App\Filament\Resources\StaffLoans\RelationManagers\RepaymentsRelationManager;
use App\Filament\Resources\StaffLoans\Tables\StaffLoansTable;
use App\Filament\Resources\StaffLoans\Schemas\StaffLoanForm;
use App\Filament\Resources\StaffLoans\Schemas\StaffLoanInfolist;
use App\Models\StaffLoan;
use Filament\Resources\Resource;

class StaffLoanResource extends Resource
{
    protected static ?string $model = StaffLoan::class;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-banknotes';
    }

    public static function getNavigationGroup(): string
    {
        return 'Payroll & HR';
    }

    public static function getNavigationLabel(): string
    {
        return 'Staff Loans';
    }

    public static function getNavigationSort(): int
    {
        return 3;
    }

    public static function getModelLabel(): string
    {
        return 'Staff Loan';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Staff Loans';
    }

    public static function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema->components(StaffLoanForm::schema());
    }

    public static function infolist(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema->components(StaffLoanInfolist::schema());
    }

    public static function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return StaffLoansTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RepaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListStaffLoans::route('/'),
            'create' => CreateStaffLoan::route('/create'),
            'view'   => ViewStaffLoan::route('/{record}'),
            'edit'   => EditStaffLoan::route('/{record}/edit'),
        ];
    }
}
