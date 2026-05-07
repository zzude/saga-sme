<?php

namespace App\Filament\Resources\CashAdvances;

use App\Filament\Resources\CashAdvances\Pages\CreateCashAdvance;
use App\Filament\Resources\CashAdvances\Pages\EditCashAdvance;
use App\Filament\Resources\CashAdvances\Pages\ListCashAdvances;
use App\Filament\Resources\CashAdvances\Pages\ViewCashAdvance;
use App\Filament\Resources\CashAdvances\Schemas\CashAdvanceForm;
use App\Filament\Resources\CashAdvances\Schemas\CashAdvanceInfolist;
use App\Filament\Resources\CashAdvances\Tables\CashAdvancesTable;
use App\Models\CashAdvance;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CashAdvanceResource extends Resource
{
    protected static ?string $model = CashAdvance::class;
    protected static ?string $recordTitleAttribute = 'advance_no';

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedBanknotes;
    }

    public static function getNavigationGroup(): ?string
    {
        return 'HR & Payroll';
    }

    public static function getNavigationLabel(): string
    {
        return 'Cash Advances';
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    public static function form(Schema $schema): Schema
    {
        return CashAdvanceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CashAdvanceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CashAdvancesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListCashAdvances::route('/'),
            'create' => CreateCashAdvance::route('/create'),
            'view'   => ViewCashAdvance::route('/{record}'),
            'edit'   => EditCashAdvance::route('/{record}/edit'),
        ];
    }
}
