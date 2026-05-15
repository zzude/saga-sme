<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AnnualBudgetResource\Pages;
use App\Models\AnnualBudget;
use App\Models\BudgetItem;
use App\Models\Account;
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

class AnnualBudgetResource extends Resource
{
    protected static ?string $model = AnnualBudget::class;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-document-chart-bar';
    }

    public static function getNavigationGroup(): string
    {
        return 'Bajet & Peruntukan';
    }

    public static function getNavigationLabel(): string
    {
        return 'Bajet Tahunan';
    }

    public static function getNavigationSort(): int
    {
        return 1;
    }

    public static function getModelLabel(): string
    {
        return 'Bajet Tahunan';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Bajet Tahunan';
    }

    public static function form(Schema $schema): Schema
    {
        $accountOptions = Account::where('is_active', true)
            ->get()
            ->mapWithKeys(fn($a) => [$a->id => $a->code . ' - ' . $a->name])
            ->toArray();

        return $schema->components([
            Section::make('Maklumat Bajet')
                ->columns(2)
                ->schema([
                    TextInput::make('budget_no')
                        ->label('No. Bajet')
                        ->placeholder('Auto-jana jika kosong')
                        ->maxLength(30),

                    Select::make('financial_year')
                        ->label('Tahun Kewangan')
                        ->options(function () {
                            $years = [];
                            for ($y = now()->year - 1; $y <= now()->year + 2; $y++) {
                                $years[$y] = $y;
                            }
                            return $years;
                        })
                        ->default(now()->year)
                        ->required(),

                    TextInput::make('title')
                        ->label('Tajuk Bajet')
                        ->columnSpanFull()
                        ->required()
                        ->maxLength(200),

                    Textarea::make('description')
                        ->label('Keterangan')
                        ->columnSpanFull()
                        ->rows(2),

                    DatePicker::make('effective_date')
                        ->label('Tarikh Kuatkuasa')
                        ->displayFormat('d/m/Y'),

                    DatePicker::make('expiry_date')
                        ->label('Tarikh Tamat')
                        ->displayFormat('d/m/Y'),

                    Hidden::make('company_id')
                        ->default(fn() => Auth::user()?->company_id ?? 1),
                ]),

            Section::make('Item Bajet')
                ->schema([
                    Repeater::make('budgetItems')
                        ->label('Baris Bajet')
                        ->defaultItems(0)
                        ->schema([
                            Select::make('account_id')
                                ->label('Akaun COA')
                                ->options($accountOptions)
                                ->searchable()
                                ->required()
                                ->columnSpan(2),

                            Select::make('object_class')
                                ->label('Kelas Objek')
                                ->options(BudgetItem::OBJECT_CLASSES)
                                ->required(),

                            TextInput::make('object_code')
                                ->label('Kod Objek')
                                ->placeholder('cth: 11000')
                                ->maxLength(20),

                            TextInput::make('description')
                                ->label('Penerangan')
                                ->required()
                                ->columnSpan(2)
                                ->maxLength(300),

                            TextInput::make('original_amount')
                                ->label('Anggaran (RM)')
                                ->numeric()
                                ->default(0)
                                ->required()
                                ->prefix('RM'),
                        ])
                        ->columns(4)
                        ->addActionLabel('+ Tambah Item')
                        ->collapsible()
                        ->itemLabel(fn(array $state): ?string => $state['description'] ?? null),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('budget_no')
                    ->label('No. Bajet')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('financial_year')
                    ->label('Tahun')
                    ->sortable(),

                TextColumn::make('title')
                    ->label('Tajuk')
                    ->searchable()
                    ->limit(40),

                TextColumn::make('total_amount')
                    ->label('Jumlah (RM)')
                    ->money('MYR')
                    ->sortable(),

                TextColumn::make('balance_amount')
                    ->label('Baki (RM)')
                    ->money('MYR')
                    ->color(fn($state) => $state < 0 ? 'danger' : 'success'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'draft'     => 'gray',
                        'submitted' => 'warning',
                        'approved'  => 'info',
                        'active'    => 'success',
                        'closed'    => 'danger',
                        default     => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => AnnualBudget::statuses()[$state] ?? $state),

                TextColumn::make('effective_date')
                    ->label('Kuatkuasa')
                    ->date('d/m/Y'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(AnnualBudget::statuses()),

                SelectFilter::make('financial_year')
                    ->label('Tahun Kewangan')
                    ->options(fn() => AnnualBudget::distinct()
                        ->pluck('financial_year', 'financial_year')
                        ->toArray()),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn(AnnualBudget $r) => $r->isEditable()),

                Action::make('submit')
                    ->label('Kemukakan')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->visible(fn(AnnualBudget $r) => $r->status === AnnualBudget::STATUS_DRAFT)
                    ->requiresConfirmation()
                    ->action(function (AnnualBudget $record) {
                        $record->update(['status' => AnnualBudget::STATUS_SUBMITTED]);
                        Notification::make()->title('Bajet dikemukakan.')->success()->send();
                    }),

                Action::make('approve')
                    ->label('Luluskan')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(AnnualBudget $r) => $r->status === AnnualBudget::STATUS_SUBMITTED)
                    ->form([
                        Textarea::make('approval_notes')->label('Nota Kelulusan')->rows(2),
                    ])
                    ->action(function (AnnualBudget $record, array $data) {
                        try {
                            app(BudgetService::class)->approveBudget($record, $data['approval_notes'] ?? null);
                            Notification::make()->title('Bajet diluluskan & diaktifkan.')->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),

                Action::make('close')
                    ->label('Tutup Bajet')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->visible(fn(AnnualBudget $r) => $r->status === AnnualBudget::STATUS_ACTIVE)
                    ->requiresConfirmation()
                    ->action(function (AnnualBudget $record) {
                        try {
                            app(BudgetService::class)->closeBudget($record);
                            Notification::make()->title('Bajet ditutup.')->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),
            ])
            ->defaultSort('financial_year', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAnnualBudgets::route('/'),
            'create' => Pages\CreateAnnualBudget::route('/create'),
            'edit'   => Pages\EditAnnualBudget::route('/{record}/edit'),
            'view'   => Pages\ViewAnnualBudget::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', Auth::user()?->company_id ?? 1);
    }
}
