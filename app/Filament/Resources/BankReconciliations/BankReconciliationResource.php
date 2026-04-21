<?php

namespace App\Filament\Resources\BankReconciliations;

use App\Filament\Resources\BankReconciliations\Pages\CreateBankReconciliation;
use App\Filament\Resources\BankReconciliations\Pages\EditBankReconciliation;
use App\Filament\Resources\BankReconciliations\Pages\ListBankReconciliations;
use App\Filament\Resources\BankReconciliations\Pages\ViewBankReconciliation;
use App\Filament\Resources\BankReconciliations\Schemas\BankReconciliationForm;
use App\Filament\Resources\BankReconciliations\Schemas\BankReconciliationInfolist;
use App\Filament\Resources\BankReconciliations\Tables\BankReconciliationsTable;
use App\Models\BankReconciliation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BankReconciliationResource extends Resource
{
    protected static ?string $model = BankReconciliation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return BankReconciliationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return BankReconciliationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BankReconciliationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBankReconciliations::route('/'),
            'create' => CreateBankReconciliation::route('/create'),
            'view' => ViewBankReconciliation::route('/{record}'),
            'edit' => EditBankReconciliation::route('/{record}/edit'),
        ];
    }
}
