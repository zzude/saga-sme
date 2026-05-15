<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WarrantAllocationResource\Pages;
use App\Models\WarrantAllocation;
use App\Models\AnnualBudget;
use App\Models\BudgetItem;
use App\Services\BudgetService;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Hidden;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class WarrantAllocationResource extends Resource
{
    protected static ?string $model = WarrantAllocation::class;

    public static function getNavigationIcon(): string { return 'heroicon-o-clipboard-document-check'; }
    public static function getNavigationGroup(): string { return 'Bajet & Peruntukan'; }
    public static function getNavigationLabel(): string { return 'Waran Peruntukan'; }
    public static function getNavigationSort(): int { return 2; }
    public static function getModelLabel(): string { return 'Waran Peruntukan'; }
    public static function getPluralModelLabel(): string { return 'Waran Peruntukan'; }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Maklumat Waran')
                ->columns(2)
                ->schema([
                    TextInput::make('warrant_no')
                        ->label('No. Waran')
                        ->placeholder('Auto-jana jika kosong')
                        ->maxLength(30),

                    Select::make('warrant_type')
                        ->label('Jenis Waran')
                        ->options(WarrantAllocation::types())
                        ->default(WarrantAllocation::TYPE_ASAL)
                        ->required(),

                    Select::make('annual_budget_id')
                        ->label('Bajet Tahunan')
                        ->options(fn() => AnnualBudget::where('company_id', Auth::user()?->company_id ?? 1)
                            ->whereIn('status', ['active', 'approved'])
                            ->pluck('title', 'id'))
                        ->searchable()
                        ->required()
                        ->columnSpanFull()
                        ->live(),

                    TextInput::make('title')
                        ->label('Tajuk Waran')
                        ->required()
                        ->columnSpanFull()
                        ->maxLength(200),

                    Textarea::make('description')
                        ->label('Keterangan')
                        ->columnSpanFull()
                        ->rows(2),

                    DatePicker::make('issue_date')
                        ->label('Tarikh Dikeluarkan')
                        ->displayFormat('d/m/Y')
                        ->default(now()),

                    DatePicker::make('expiry_date')
                        ->label('Tarikh Tamat')
                        ->displayFormat('d/m/Y'),

                    TextInput::make('reference_doc')
                        ->label('Rujukan Dokumen')
                        ->maxLength(100)
                        ->columnSpanFull(),

                    Hidden::make('company_id')
                        ->default(fn() => Auth::user()?->company_id ?? 1),
                ]),

            Section::make('Item Waran')
                ->schema([
                    Repeater::make('warrantItems')
                        ->label('Baris Waran')
                        ->relationship()
                        ->schema([
                            Select::make('budget_item_id')
                                ->label('Item Bajet')
                                ->options(fn($get) =>
                                    BudgetItem::where('annual_budget_id', $get('../../annual_budget_id'))
                                        ->pluck('description', 'id'))
                                ->searchable()
                                ->required()
                                ->columnSpan(2),

                            TextInput::make('warrant_amount')
                                ->label('Amaun Waran (RM)')
                                ->numeric()
                                ->required()
                                ->prefix('RM'),

                            Textarea::make('notes')
                                ->label('Nota')
                                ->rows(1),

                            Hidden::make('company_id')
                                ->default(fn() => Auth::user()?->company_id ?? 1),
                        ])
                        ->columns(4)
                        ->addActionLabel('+ Tambah Item')
                        ->collapsible(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('warrant_no')->label('No. Waran')->searchable()->sortable()->weight('bold'),
                TextColumn::make('annualBudget.title')->label('Bajet')->limit(30),
                TextColumn::make('warrant_type')
                    ->label('Jenis')
                    ->formatStateUsing(fn($state) => WarrantAllocation::types()[$state] ?? $state),
                TextColumn::make('total_amount')->label('Jumlah (RM)')->money('MYR'),
                TextColumn::make('balance_amount')->label('Baki (RM)')->money('MYR')
                    ->color(fn($state) => $state <= 0 ? 'danger' : 'success'),
                TextColumn::make('status')->label('Status')->badge()
                    ->color(fn($state) => match ($state) {
                        'draft' => 'gray', 'issued' => 'info', 'active' => 'success',
                        'exhausted' => 'warning', 'cancelled' => 'danger', default => 'gray',
                    })
                    ->formatStateUsing(fn($state) => WarrantAllocation::statuses()[$state] ?? $state),
                TextColumn::make('issue_date')->label('Tarikh')->date('d/m/Y'),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options(WarrantAllocation::statuses()),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn(WarrantAllocation $r) => $r->status === WarrantAllocation::STATUS_DRAFT),

                Action::make('issue')
                    ->label('Keluarkan')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->visible(fn(WarrantAllocation $r) => $r->status === WarrantAllocation::STATUS_DRAFT)
                    ->requiresConfirmation()
                    ->action(function (WarrantAllocation $record) {
                        try {
                            app(BudgetService::class)->issueWarrant($record);
                            Notification::make()->title('Waran berjaya dikeluarkan.')->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),

                Action::make('activate')
                    ->label('Aktifkan')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(WarrantAllocation $r) => $r->status === WarrantAllocation::STATUS_ISSUED)
                    ->requiresConfirmation()
                    ->action(function (WarrantAllocation $record) {
                        try {
                            app(BudgetService::class)->activateWarrant($record);
                            Notification::make()->title('Waran diaktifkan.')->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),

                Action::make('cancel')
                    ->label('Batal')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn(WarrantAllocation $r) => in_array($r->status, [
                        WarrantAllocation::STATUS_ISSUED, WarrantAllocation::STATUS_ACTIVE,
                    ]))
                    ->form([
                        Textarea::make('approval_notes')->label('Sebab Pembatalan')->required(),
                    ])
                    ->action(function (WarrantAllocation $record, array $data) {
                        try {
                            app(BudgetService::class)->cancelWarrant($record, $data['approval_notes']);
                            Notification::make()->title('Waran dibatalkan.')->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListWarrantAllocations::route('/'),
            'create' => Pages\CreateWarrantAllocation::route('/create'),
            'edit'   => Pages\EditWarrantAllocation::route('/{record}/edit'),
            'view'   => Pages\ViewWarrantAllocation::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('company_id', Auth::user()?->company_id ?? 1);
    }
}


