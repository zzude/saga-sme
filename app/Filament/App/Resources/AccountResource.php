<?php

namespace App\Filament\App\Resources;

use App\Enums\AccountLevel;
use App\Enums\AccountType;
use App\Filament\App\Resources\AccountResource\Pages;
use App\Models\Account;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AccountResource extends Resource
{
    protected static ?string $model = Account::class;

    // ── Navigation (methods, not static properties — Filament 5) ────────────

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-book-open';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Accounting Setup';
    }

    public static function getNavigationLabel(): string
    {
        return 'Chart of Accounts';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::postable()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    // ── Form (Schema $schema / ->components([]) — Filament 5) ───────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Account Details')
                ->columns(2)
                ->schema([
                    Select::make('level')
                        ->label('Account Level')
                        ->options(AccountLevel::class)
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (Set $set) {
                            $set('parent_id', null);
                            $set('type', null);
                        }),

                    Select::make('type')
                        ->label('Account Type')
                        ->options(AccountType::class)
                        ->required(),

                    TextInput::make('code')
                        ->label('Account Code')
                        ->required()
                        ->numeric()
                        ->minValue(1000)
                        ->maxValue(5999)
                        ->unique(
                            table: 'accounts',
                            column: 'code',
                            ignoreRecord: true,
                            modifyRuleUsing: fn ($rule) => $rule->where('company_id', auth()->user()?->company_id),
                        )
                        ->placeholder('e.g. 1110'),

                    TextInput::make('name')
                        ->label('Account Name')
                        ->required()
                        ->maxLength(255),

                    Select::make('parent_id')
                        ->label('Parent Account')
                        ->options(function (Get $get) {
                            $levelValue = static::levelValue($get('level'));

                            if ($levelValue <= AccountLevel::Category->value) {
                                return [];
                            }

                            $parentLevelValue = $levelValue - 1;

                            return Account::withoutGlobalScopes()
                                ->where('company_id', auth()->user()?->company_id)
                                ->where('level', $parentLevelValue)
                                ->where('is_active', true)
                                ->orderBy('code')
                                ->get()
                                ->mapWithKeys(fn (Account $a) => [$a->id => $a->code . ' — ' . $a->name]);
                        })
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function (?int $state, Set $set) {
                            if ($state) {
                                $parent = Account::withoutGlobalScopes()->find($state);
                                if ($parent) {
                                    $set('type', $parent->type->value);
                                }
                            }
                        })
                        ->visible(fn (Get $get) => static::levelValue($get('level')) > AccountLevel::Category->value)
                        ->required(fn (Get $get) => static::levelValue($get('level')) > AccountLevel::Category->value)
                        ->helperText('Select the parent account. Type is inherited from parent.')
                        ->columnSpanFull(),

                    Textarea::make('description')
                        ->label('Description')
                        ->rows(2)
                        ->maxLength(500)
                        ->columnSpanFull(),
                ]),

            Section::make('Status')
                ->columns(1)
                ->compact()
                ->schema([
                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->helperText('Inactive accounts cannot be selected for new transactions.'),
                ]),
        ]);
    }

    // ── Table ────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->sortable()
                    ->searchable()
                    ->weight(fn (Account $record) => match ($record->level) {
                        AccountLevel::Category => 'bold',
                        default                => 'normal',
                    })
                    ->width('100px'),

                TextColumn::make('level')
                    ->label('Level')
                    ->badge()
                    ->sortable()
                    ->width('110px'),

                TextColumn::make('name')
                    ->label('Account Name')
                    ->searchable()
                    ->description(fn (Account $record): ?string => $record->parent
                        ? $record->parent->code . ' — ' . $record->parent->name
                        : null),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->width('70px'),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Account Type')
                    ->options(AccountType::class),

                SelectFilter::make('level')
                    ->label('Level')
                    ->options(AccountLevel::class),

                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('code', 'asc')
            ->striped()
            ->paginated([25, 50, 100, 'all']);
    }

    // ── Query ────────────────────────────────────────────────────────────────

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('parent');
    }

    // ── Pages ────────────────────────────────────────────────────────────────

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAccounts::route('/'),
            'create' => Pages\CreateAccount::route('/create'),
            'edit'   => Pages\EditAccount::route('/{record}/edit'),
        ];
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Normalise the level value from form state.
     *
     * $get('level') returns an AccountLevel enum instance when Filament fills
     * the form from an existing record (model cast), but returns a raw int when
     * the user selects a value on a new form. Handle both.
     */
    private static function levelValue(mixed $level): int
    {
        return $level instanceof AccountLevel ? $level->value : (int) $level;
    }
}
