<?php
namespace App\Filament\Resources;

use App\Filament\Resources\ExpenditureObjectResource\Pages;
use App\Models\ExpenditureObject;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;

class ExpenditureObjectResource extends Resource
{
    protected static ?string $model = ExpenditureObject::class;

    public static function getNavigationGroup(): string { return 'Klasifikasi Kerajaan'; }
    public static function getNavigationIcon(): string { return 'heroicon-o-rectangle-stack'; }
    public static function getNavigationLabel(): string { return 'Objek Sebagai'; }
    public static function getNavigationSort(): int { return 3; }
    public static function getModelLabel(): string { return 'Objek Sebagai'; }
    public static function getPluralModelLabel(): string { return 'Objek Sebagai'; }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Maklumat Objek')->columns(2)->schema([
                TextInput::make('code')->label('Kod')->required()->maxLength(10)->placeholder('e.g. 21000'),
                Select::make('level')->label('Peringkat')->options(['objek' => 'Objek', 'sub_objek' => 'Sub Objek'])->required()->default('objek')->reactive(),
                TextInput::make('name')->label('Nama')->required()->columnSpanFull(),
                Select::make('parent_id')
                    ->label('Objek Induk')
                    ->options(fn () => ExpenditureObject::where('level', 'objek')->orderBy('code')->get()->mapWithKeys(fn ($o) => [$o->id => $o->code . ' — ' . $o->name]))
                    ->searchable()->nullable()->placeholder('— Tiada (Objek Utama) —')
                    ->hidden(fn ($get) => $get('level') !== 'sub_objek'),
                Select::make('category')->label('Kategori')->options(['mengurus' => 'Mengurus', 'pembangunan' => 'Pembangunan'])->required()->default('mengurus'),
                Textarea::make('description')->label('Keterangan')->rows(2)->columnSpanFull(),
                Toggle::make('is_active')->label('Aktif')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('code')->label('Kod')->searchable()->sortable()->badge()->color('gray'),
            TextColumn::make('name')->label('Nama')->searchable()->limit(40),
            TextColumn::make('level')->label('Peringkat')->badge()->color(fn ($state) => $state === 'objek' ? 'primary' : 'info')->formatStateUsing(fn ($state) => $state === 'objek' ? 'Objek' : 'Sub Objek'),
            TextColumn::make('category')->label('Kategori')->badge()->color(fn ($state) => $state === 'mengurus' ? 'success' : 'warning')->formatStateUsing(fn ($state) => $state === 'mengurus' ? 'Mengurus' : 'Pembangunan'),
            TextColumn::make('parent.name')->label('Objek Induk')->placeholder('—')->limit(25)->toggleable(),
            IconColumn::make('is_active')->label('Aktif')->boolean(),
        ])
        ->filters([
            SelectFilter::make('level')->options(['objek' => 'Objek', 'sub_objek' => 'Sub Objek']),
            SelectFilter::make('category')->options(['mengurus' => 'Mengurus', 'pembangunan' => 'Pembangunan']),
        ])
        ->defaultSort('code');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListExpenditureObjects::route('/'),
            'create' => Pages\CreateExpenditureObject::route('/create'),
            'edit'   => Pages\EditExpenditureObject::route('/{record}/edit'),
        ];
    }
}
