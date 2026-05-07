<?php

namespace App\Filament\Resources\LeaveApplications;

use App\Filament\Resources\LeaveApplications\Pages\CreateLeaveApplication;
use App\Filament\Resources\LeaveApplications\Pages\EditLeaveApplication;
use App\Filament\Resources\LeaveApplications\Pages\ListLeaveApplications;
use App\Filament\Resources\LeaveApplications\Pages\ViewLeaveApplication;
use App\Filament\Resources\LeaveApplications\Schemas\LeaveApplicationForm;
use App\Filament\Resources\LeaveApplications\Schemas\LeaveApplicationInfolist;
use App\Filament\Resources\LeaveApplications\Tables\LeaveApplicationsTable;
use App\Models\LeaveApplication;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LeaveApplicationResource extends Resource
{
    protected static ?string $model = LeaveApplication::class;
    protected static ?string $recordTitleAttribute = 'application_no';

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedClipboardDocumentList;
    }

    public static function getNavigationGroup(): ?string
    {
        return 'HR & Payroll';
    }

    public static function getNavigationLabel(): string
    {
        return 'Permohonan Cuti';
    }

    public static function getNavigationSort(): ?int
    {
        return 5;
    }

    public static function form(Schema $schema): Schema
    {
        return LeaveApplicationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LeaveApplicationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LeaveApplicationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListLeaveApplications::route('/'),
            'create' => CreateLeaveApplication::route('/create'),
            'view'   => ViewLeaveApplication::route('/{record}'),
            'edit'   => EditLeaveApplication::route('/{record}/edit'),
        ];
    }
}
