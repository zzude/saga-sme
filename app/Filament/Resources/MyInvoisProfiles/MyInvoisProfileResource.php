<?php

namespace App\Filament\Resources\MyInvoisProfiles;

use App\Filament\Resources\MyInvoisProfiles\Pages\CreateMyInvoisProfile;
use App\Filament\Resources\MyInvoisProfiles\Pages\EditMyInvoisProfile;
use App\Filament\Resources\MyInvoisProfiles\Pages\ListMyInvoisProfiles;
use App\Filament\Resources\MyInvoisProfiles\Schemas\MyInvoisProfileForm;
use App\Filament\Resources\MyInvoisProfiles\Tables\MyInvoisProfilesTable;
use App\Models\MyInvoisProfile;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MyInvoisProfileResource extends Resource
{
    protected static ?string $model = MyInvoisProfile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'client_id';

    public static function form(Schema $schema): Schema
    {
        return MyInvoisProfileForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MyInvoisProfilesTable::configure($table);
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
            'index' => ListMyInvoisProfiles::route('/'),
            'create' => CreateMyInvoisProfile::route('/create'),
            'edit' => EditMyInvoisProfile::route('/{record}/edit'),
        ];
    }
}
