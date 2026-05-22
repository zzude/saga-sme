<?php
namespace App\Filament\Resources;

use App\Filament\Resources\PtjResource\Pages;
use App\Models\Ptj;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;

class PtjResource extends Resource
{
    protected static ?string $model = Ptj::class;

    public static function getNavigationGroup(): string { return 'Klasifikasi Kerajaan'; }
    public static function getNavigationIcon(): string { return 'heroicon-o-building-office-2'; }
    public static function getNavigationLabel(): string { return 'PTJ'; }
    public static function getNavigationSort(): int { return 1; }
    public static function getModelLabel(): string { return 'PTJ'; }
    public static function getPluralModelLabel(): string { return 'Pusat Tanggungjawab (PTJ)'; }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Maklumat PTJ')->columns(2)->schema([
                TextInput::make('code')->label('Kod PTJ')->required()->maxLength(20)->placeholder('e.g. J001'),
                TextInput::make('short_name')->label('Singkatan')->maxLength(20)->placeholder('e.g. KEW'),
                TextInput::make('name')->label('Nama PTJ')->required()->columnSpanFull()->placeholder('e.g. Jabatan Kewangan'),
                Textarea::make('description')->label('Keterangan')->rows(2)->columnSpanFull(),
                Select::make('head_id')->label('Ketua PTJ')->options(User::orderBy('name')->pluck('name', 'id'))->searchable()->nullable()->placeholder('— Tiada —'),
                Toggle::make('is_active')->label('Aktif')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('code')->label('Kod')->badge()->color('primary')->searchable()->sortable(),
            TextColumn::make('name')->label('Nama PTJ')->searchable()->sortable(),
            TextColumn::make('short_name')->label('Singkatan')->placeholder('-'),
            TextColumn::make('programs_count')->label('Program')->counts('programs')->badge()->color('info'),
            TextColumn::make('head.name')->label('Ketua')->placeholder('-')->toggleable(),
            IconColumn::make('is_active')->label('Aktif')->boolean(),
        ])->defaultSort('code');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPtj::route('/'),
            'create' => Pages\CreatePtj::route('/create'),
            'view'   => Pages\ViewPtj::route('/{record}'),
            'edit'   => Pages\EditPtj::route('/{record}/edit'),
        ];
    }
}
