<?php
namespace App\Filament\Resources;

use App\Filament\Resources\RevenueCodeResource\Pages;
use App\Models\RevenueCode;
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

class RevenueCodeResource extends Resource
{
    protected static ?string $model = RevenueCode::class;

    public static function getNavigationGroup(): string { return 'Klasifikasi Kerajaan'; }
    public static function getNavigationIcon(): string { return 'heroicon-o-banknotes'; }
    public static function getNavigationLabel(): string { return 'Kod Hasil'; }
    public static function getNavigationSort(): int { return 4; }
    public static function getModelLabel(): string { return 'Kod Hasil'; }
    public static function getPluralModelLabel(): string { return 'Kod Hasil'; }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Maklumat Kod Hasil')->columns(2)->schema([
                TextInput::make('code')->label('Kod')->required()->maxLength(10)->placeholder('e.g. 10000'),
                Select::make('level')->label('Peringkat')->options(['head' => 'Kepala', 'sub' => 'Sub'])->required()->default('head')->reactive(),
                TextInput::make('name')->label('Nama')->required()->columnSpanFull(),
                Select::make('parent_id')
                    ->label('Kepala Induk')
                    ->options(fn () => RevenueCode::where('level', 'head')->orderBy('code')->get()->mapWithKeys(fn ($r) => [$r->id => $r->code . ' — ' . $r->name]))
                    ->searchable()->nullable()->placeholder('— Tiada (Kepala Utama) —')
                    ->hidden(fn ($get) => $get('level') !== 'sub'),
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
            TextColumn::make('level')->label('Peringkat')->badge()->color(fn ($state) => $state === 'head' ? 'primary' : 'info')->formatStateUsing(fn ($state) => $state === 'head' ? 'Kepala' : 'Sub'),
            TextColumn::make('parent.name')->label('Kepala Induk')->placeholder('—')->limit(25)->toggleable(),
            IconColumn::make('is_active')->label('Aktif')->boolean(),
        ])
        ->filters([
            SelectFilter::make('level')->options(['head' => 'Kepala', 'sub' => 'Sub']),
        ])
        ->defaultSort('code');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRevenueCodes::route('/'),
            'create' => Pages\CreateRevenueCode::route('/create'),
            'edit'   => Pages\EditRevenueCode::route('/{record}/edit'),
        ];
    }
}
