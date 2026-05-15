<?php
// ════════════════════════════════════════════════════════════════
// VirementResource.php
// ════════════════════════════════════════════════════════════════
namespace App\Filament\Resources;

use App\Filament\Resources\VirementResource\Pages;
use App\Models\Virement;
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

class VirementResource extends Resource
{
    protected static ?string $model = Virement::class;

    public static function getNavigationIcon(): string { return 'heroicon-o-arrows-right-left'; }
    public static function getNavigationGroup(): string { return 'Bajet & Peruntukan'; }
    public static function getNavigationLabel(): string { return 'Virement'; }
    public static function getNavigationSort(): int { return 3; }
    public static function getModelLabel(): string { return 'Virement'; }
    public static function getPluralModelLabel(): string { return 'Virement'; }

    public static function form(Schema $schema): Schema
    {
        $budgetItemOptions = fn(\Filament\Forms\Get $get) =>
            BudgetItem::where('annual_budget_id', $get('annual_budget_id'))->pluck('description', 'id');

        return $schema->components([
            Section::make('Maklumat Virement')
                ->columns(2)
                ->schema([
                    TextInput::make('virement_no')->label('No. Virement')->placeholder('Auto-jana')->maxLength(30),
                    Select::make('annual_budget_id')
                        ->label('Bajet Tahunan')
                        ->options(fn() => AnnualBudget::where('company_id', Auth::user()?->company_id ?? 1)
                            ->where('status', 'active')->pluck('title', 'id'))
                        ->searchable()->required()->live(),
                    TextInput::make('title')->label('Tajuk')->required()->columnSpanFull()->maxLength(200),
                    Textarea::make('justification')->label('Justifikasi')->columnSpanFull()->rows(2),
                    DatePicker::make('virement_date')->label('Tarikh')->displayFormat('d/m/Y')->default(now())->required(),
                    TextInput::make('approval_reference')->label('Rujukan Kelulusan')->maxLength(100),
                    Hidden::make('company_id')->default(fn() => Auth::user()?->company_id ?? 1),
                ]),

            Section::make('Pindahan DARI (FROM)')
                ->schema([
                    Repeater::make('fromItems')->label('Item dikurangkan')->relationship('fromItems')
                        ->schema([
                            Select::make('budget_item_id')->label('Item Bajet')->options($budgetItemOptions)
                                ->searchable()->required()->columnSpan(2),
                            TextInput::make('amount')->label('Amaun (RM)')->numeric()->required()->prefix('RM'),
                            Textarea::make('notes')->label('Nota')->rows(1),
                            Hidden::make('direction')->default('from'),
                            Hidden::make('company_id')->default(fn() => Auth::user()?->company_id ?? 1),
                        ])->columns(4)->addActionLabel('+ Tambah FROM')->collapsible(),
                ]),

            Section::make('Pindahan KE (TO)')
                ->schema([
                    Repeater::make('toItems')->label('Item ditambah')->relationship('toItems')
                        ->schema([
                            Select::make('budget_item_id')->label('Item Bajet')->options($budgetItemOptions)
                                ->searchable()->required()->columnSpan(2),
                            TextInput::make('amount')->label('Amaun (RM)')->numeric()->required()->prefix('RM'),
                            Textarea::make('notes')->label('Nota')->rows(1),
                            Hidden::make('direction')->default('to'),
                            Hidden::make('company_id')->default(fn() => Auth::user()?->company_id ?? 1),
                        ])->columns(4)->addActionLabel('+ Tambah TO')->collapsible(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('virement_no')->label('No. Virement')->searchable()->weight('bold'),
                TextColumn::make('annualBudget.title')->label('Bajet')->limit(25),
                TextColumn::make('title')->label('Tajuk')->limit(30),
                TextColumn::make('total_amount')->label('Jumlah (RM)')->money('MYR'),
                TextColumn::make('virement_date')->label('Tarikh')->date('d/m/Y'),
                TextColumn::make('status')->label('Status')->badge()
                    ->color(fn($state) => match ($state) {
                        'draft' => 'gray', 'pending_approval' => 'warning',
                        'approved' => 'info', 'rejected' => 'danger', 'posted' => 'success', default => 'gray',
                    })
                    ->formatStateUsing(fn($state) => Virement::statuses()[$state] ?? $state),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options(Virement::statuses()),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make()->visible(fn(Virement $r) => $r->isEditable()),

                Action::make('submit')->label('Kemukakan')->icon('heroicon-o-paper-airplane')->color('warning')
                    ->visible(fn(Virement $r) => $r->status === Virement::STATUS_DRAFT)
                    ->requiresConfirmation()
                    ->action(function (Virement $record) {
                        try {
                            app(BudgetService::class)->submitVirement($record);
                            Notification::make()->title('Virement dikemukakan.')->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),

                Action::make('approve')->label('Luluskan & Pos')->icon('heroicon-o-check-circle')->color('success')
                    ->visible(fn(Virement $r) => $r->status === Virement::STATUS_PENDING)
                    ->form([Textarea::make('approval_notes')->label('Nota Kelulusan')])
                    ->action(function (Virement $record, array $data) {
                        try {
                            app(BudgetService::class)->approveAndPostVirement($record, $data['approval_notes'] ?? null);
                            Notification::make()->title('Virement diluluskan & dipos.')->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),

                Action::make('reject')->label('Tolak')->icon('heroicon-o-x-circle')->color('danger')
                    ->visible(fn(Virement $r) => $r->status === Virement::STATUS_PENDING)
                    ->form([Textarea::make('approval_notes')->label('Sebab Penolakan')->required()])
                    ->action(function (Virement $record, array $data) {
                        try {
                            app(BudgetService::class)->rejectVirement($record, $data['approval_notes']);
                            Notification::make()->title('Virement ditolak.')->success()->send();
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
            'index'  => Pages\ListVirements::route('/'),
            'create' => Pages\CreateVirement::route('/create'),
            'edit'   => Pages\EditVirement::route('/{record}/edit'),
            'view'   => Pages\ViewVirement::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('company_id', Auth::user()?->company_id ?? 1);
    }
}

