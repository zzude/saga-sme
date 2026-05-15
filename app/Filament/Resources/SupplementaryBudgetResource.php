<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplementaryBudgetResource\Pages;
use App\Models\SupplementaryBudget;
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
use Filament\Forms\Components\Hidden;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class SupplementaryBudgetResource extends Resource
{
    protected static ?string $model = SupplementaryBudget::class;

    public static function getNavigationIcon(): string { return 'heroicon-o-plus-circle'; }
    public static function getNavigationGroup(): string { return 'Bajet & Peruntukan'; }
    public static function getNavigationLabel(): string { return 'Tambahan Peruntukan'; }
    public static function getNavigationSort(): int { return 4; }
    public static function getModelLabel(): string { return 'Tambahan Peruntukan'; }
    public static function getPluralModelLabel(): string { return 'Tambahan Peruntukan'; }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Maklumat Tambahan Peruntukan')
                ->columns(2)
                ->schema([
                    TextInput::make('supplementary_no')->label('No. Tambahan')->placeholder('Auto-jana')->maxLength(30),
                    Select::make('annual_budget_id')
                        ->label('Bajet Tahunan')
                        ->options(fn() => AnnualBudget::where('company_id', Auth::user()?->company_id ?? 1)
                            ->where('status', 'active')->pluck('title', 'id'))
                        ->searchable()->required()->live(),
                    TextInput::make('title')->label('Tajuk')->required()->columnSpanFull()->maxLength(200),
                    Textarea::make('justification')->label('Justifikasi')->columnSpanFull()->rows(3)->required(),
                    Select::make('budget_item_id')
                        ->label('Item Bajet')
                        ->options(fn($get) =>
                            BudgetItem::where('annual_budget_id', $get('annual_budget_id'))->pluck('description', 'id'))
                        ->searchable()->required(),
                    TextInput::make('amount')->label('Amaun Tambahan (RM)')->numeric()->required()->prefix('RM')
                        ->helperText('Positif = tambah, negatif = potong'),
                    Select::make('funding_source')->label('Sumber Peruntukan')->options(SupplementaryBudget::FUNDING_SOURCES),
                    DatePicker::make('effective_date')->label('Tarikh Kuatkuasa')->displayFormat('d/m/Y')->default(now())->required(),
                    TextInput::make('supporting_doc')->label('Dokumen Sokongan')->maxLength(150)->columnSpanFull(),
                    Hidden::make('company_id')->default(fn() => Auth::user()?->company_id ?? 1),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('supplementary_no')->label('No.')->searchable()->weight('bold'),
                TextColumn::make('annualBudget.title')->label('Bajet')->limit(25),
                TextColumn::make('title')->label('Tajuk')->limit(30),
                TextColumn::make('budgetItem.description')->label('Item Bajet')->limit(25),
                TextColumn::make('amount')->label('Amaun (RM)')->money('MYR')
                    ->color(fn($state) => $state < 0 ? 'danger' : 'success'),
                TextColumn::make('effective_date')->label('Tarikh')->date('d/m/Y'),
                TextColumn::make('status')->label('Status')->badge()
                    ->color(fn($state) => match ($state) {
                        'draft' => 'gray', 'submitted' => 'warning', 'approved' => 'info',
                        'rejected' => 'danger', 'posted' => 'success', default => 'gray',
                    })
                    ->formatStateUsing(fn($state) => SupplementaryBudget::statuses()[$state] ?? $state),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make()->visible(fn(SupplementaryBudget $r) => $r->isEditable()),

                Action::make('submit')->label('Kemukakan')->icon('heroicon-o-paper-airplane')->color('warning')
                    ->visible(fn(SupplementaryBudget $r) => $r->status === SupplementaryBudget::STATUS_DRAFT)
                    ->requiresConfirmation()
                    ->action(function (SupplementaryBudget $record) {
                        $record->update(['status' => SupplementaryBudget::STATUS_SUBMITTED, 'prepared_by' => Auth::id()]);
                        Notification::make()->title('Dikemukakan.')->success()->send();
                    }),

                Action::make('approve')->label('Luluskan & Pos')->icon('heroicon-o-check-circle')->color('success')
                    ->visible(fn(SupplementaryBudget $r) => $r->status === SupplementaryBudget::STATUS_SUBMITTED)
                    ->form([Textarea::make('approval_notes')->label('Nota')])
                    ->action(function (SupplementaryBudget $record, array $data) {
                        try {
                            app(BudgetService::class)->approveAndPostSupplementary($record, $data['approval_notes'] ?? null);
                            Notification::make()->title('Tambahan peruntukan dipos.')->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),

                Action::make('reject')->label('Tolak')->icon('heroicon-o-x-circle')->color('danger')
                    ->visible(fn(SupplementaryBudget $r) => $r->status === SupplementaryBudget::STATUS_SUBMITTED)
                    ->form([Textarea::make('approval_notes')->label('Sebab Penolakan')->required()])
                    ->action(function (SupplementaryBudget $record, array $data) {
                        try {
                            app(BudgetService::class)->rejectSupplementary($record, $data['approval_notes']);
                            Notification::make()->title('Tambahan ditolak.')->success()->send();
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
            'index'  => Pages\ListSupplementaryBudgets::route('/'),
            'create' => Pages\CreateSupplementaryBudget::route('/create'),
            'edit'   => Pages\EditSupplementaryBudget::route('/{record}/edit'),
            'view'   => Pages\ViewSupplementaryBudget::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('company_id', Auth::user()?->company_id ?? 1);
    }
}


