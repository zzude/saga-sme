<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\JournalResource\Pages;
use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\JournalHeader;
use App\Services\JournalService;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\Action as TableAction;
use Illuminate\Support\Facades\Auth;

class JournalResource extends Resource
{
    protected static ?string $model = JournalHeader::class;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-document-text';
    }

    public static function getNavigationGroup(): string
    {
        return 'Transactions';
    }

    public static function getNavigationLabel(): string
    {
        return 'Journal Entries';
    }

    public static function getModelLabel(): string
    {
        return 'Journal Entry';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Journal Details')
                ->columns(2)
                ->disabled(fn (?JournalHeader $record) => $record && !$record->isDraft())
                ->components([
                    Select::make('period_id')
                        ->label('Accounting Period')
                        ->options(fn () => AccountingPeriod::where('status', 'open')
                            ->orderBy('start_date')
                            ->pluck('name', 'id'))
                        ->required()
                        ->searchable(),

                    \Filament\Forms\Components\DatePicker::make('date')
                        ->label('Date')
                        ->required()
                        ->default(now()),

                    Select::make('source_type')
                        ->label('Source Type')
                        ->options([
                            'manual'          => 'Manual',
                            'opening_balance' => 'Opening Balance',
                            'adjustment'      => 'Adjustment',
                        ])
                        ->default('manual')
                        ->required(),

                    TextInput::make('summary_text')
                        ->label('Summary')
                        ->required()
                        ->maxLength(255)
                        ->helperText('Ringkasan automatik — anda boleh ubah')
                        ->columnSpanFull(),
                ]),

            Section::make('Journal Lines')
                ->disabled(fn (?JournalHeader $record) => $record && !$record->isDraft())
                ->components([
                    Repeater::make('lines')
                        ->relationship('lines')
                        ->schema([
                            Select::make('account_id')
                                ->label('Account')
                                ->options(fn () => Account::postable()
                                    ->orderBy('code')
                                    ->get()
                                    ->mapWithKeys(fn ($a) => [$a->id => $a->code . ' – ' . $a->name]))
                                ->searchable()
                                ->required()
                                ->columnSpan(4),

                            TextInput::make('debit')
                                ->label('Debit')
                                ->numeric()
                                ->default(0)
                                ->minValue(0)
                                ->extraInputAttributes(['style' => 'text-align:right; font-family:monospace'])
                                ->columnSpan(2),

                            TextInput::make('credit')
                                ->label('Credit')
                                ->numeric()
                                ->default(0)
                                ->minValue(0)
                                ->extraInputAttributes(['style' => 'text-align:right; font-family:monospace'])
                                ->columnSpan(2),

                            TextInput::make('description')
                                ->label('Description')
                                ->maxLength(255)
                                ->columnSpan(4),
                        ])
                        ->columns(12)
                        ->columnSpanFull()
                        ->minItems(2)
                        ->addActionLabel('+ Add Line')
                        ->reorderable(false),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference_no')
                    ->label('Reference')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('period.name')
                    ->label('Period')
                    ->sortable(),

                TextColumn::make('summary_text')
                    ->label('Summary')
                    ->limit(40)
                    ->searchable(),

                TextColumn::make('source_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'manual'          => 'info',
                        'opening_balance' => 'warning',
                        'adjustment'      => 'gray',
                        'reversal'        => 'danger',
                        default           => 'gray',
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft'  => 'warning',
                        'posted' => 'success',
                        'voided' => 'danger',
                    }),

                TextColumn::make('createdBy.name')
                    ->label('Created By')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft'  => 'Draft',
                        'posted' => 'Posted',
                        'voided' => 'Voided',
                    ]),

                SelectFilter::make('source_type')
                    ->label('Type')
                    ->options([
                        'manual'          => 'Manual',
                        'opening_balance' => 'Opening Balance',
                        'adjustment'      => 'Adjustment',
                        'reversal'        => 'Reversal',
                    ]),
            ])
            ->actions([
                TableAction::make('post')
                    ->label('Post')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (JournalHeader $record) => 
                        $record->isDraft() && 
                        Auth::user()?->hasAnyRole(['super_admin', 'admin', 'approver'])
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Post Journal Entry')
                    ->modalDescription('Journal yang di-post tidak boleh diedit. Pastikan semua entries betul.')
                    ->action(function (JournalHeader $record) {
                        try {
                            app(JournalService::class)->post($record);
                            \Filament\Notifications\Notification::make()
                                ->title('Journal posted successfully!')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                TableAction::make('void')
                    ->label('Void')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (JournalHeader $record) => 
                        $record->isPosted() && 
                        Auth::user()?->hasAnyRole(['super_admin', 'admin'])
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Void Journal Entry')
                    ->modalDescription('Ini akan create reversal entry automatik. Tidak boleh undo!')
                    ->form([
                        \Filament\Forms\Components\Textarea::make('void_reason')
                            ->label('Sebab Void')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (JournalHeader $record, array $data) {
                        try {
                            app(JournalService::class)->void($record, $data['void_reason']);
                            \Filament\Notifications\Notification::make()
                                ->title('Journal voided — reversal entry created!')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),                    
                EditAction::make()
                    ->visible(fn (JournalHeader $record) => $record->isDraft() && 
                    Auth::user()?->hasAnyRole(['super_admin', 'admin', 'user'])),
            ])
            ->defaultSort('date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListJournals::route('/'),
            'create' => Pages\CreateJournal::route('/create'),
            'edit'   => Pages\EditJournal::route('/{record}/edit'),
        ];
    }
}