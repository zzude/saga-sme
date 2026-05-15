<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GovernmentBankAccountResource\Pages;
use App\Models\GovernmentBankAccount;
use App\Services\BudgetService;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Hidden;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class GovernmentBankAccountResource extends Resource
{
    protected static ?string $model = GovernmentBankAccount::class;

    public static function getNavigationIcon(): string { return 'heroicon-o-building-library'; }
    public static function getNavigationGroup(): string { return 'Bajet & Peruntukan'; }
    public static function getNavigationLabel(): string { return 'Akaun Bank Kerajaan'; }
    public static function getNavigationSort(): int { return 5; }
    public static function getModelLabel(): string { return 'Akaun Bank'; }
    public static function getPluralModelLabel(): string { return 'Akaun Bank Kerajaan'; }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Maklumat Akaun Bank')
                ->columns(2)
                ->schema([
                    TextInput::make('gov_account_code')->label('Kod Akaun Kerajaan')->maxLength(50)->placeholder('cth: A001'),
                    Select::make('account_type')->label('Jenis Akaun')
                        ->options(GovernmentBankAccount::ACCOUNT_TYPES)->default('am')->required(),
                    TextInput::make('account_name')->label('Nama Akaun')->required()->columnSpanFull()->maxLength(150),
                    TextInput::make('account_number')->label('No. Akaun Bank')->required()->maxLength(50),
                    TextInput::make('bank_name')->label('Nama Bank')->required()->maxLength(100),
                    TextInput::make('bank_branch')->label('Cawangan')->maxLength(100),
                    TextInput::make('swift_code')->label('Kod SWIFT')->maxLength(20),
                    Select::make('currency')->label('Matawang')
                        ->options(['MYR' => 'MYR', 'USD' => 'USD', 'SGD' => 'SGD'])->default('MYR')->required(),
                    Select::make('account_id')->label('Akaun COA')
                        ->options(fn() => \App\Models\Account::where('is_active', true)->get()->mapWithKeys(fn($a) => [$a->id => $a->code . ' - ' . $a->name])->toArray())
                        ->searchable()->preload()->required(),
                    TextInput::make('overdraft_limit')->label('Had Overdraf (RM)')->numeric()->default(0)->prefix('RM'),
                    Toggle::make('is_active')->label('Akaun Aktif')->default(true),
                    Textarea::make('notes')->label('Nota')->columnSpanFull()->rows(2),
                    Hidden::make('company_id')->default(fn() => Auth::user()?->company_id ?? 1),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('gov_account_code')->label('Kod')->searchable(),
                TextColumn::make('account_name')->label('Nama Akaun')->searchable()->weight('bold'),
                TextColumn::make('account_number')->label('No. Akaun'),
                TextColumn::make('bank_name')->label('Bank'),
                TextColumn::make('account_type')
                    ->label('Jenis')
                    ->formatStateUsing(fn($state) => GovernmentBankAccount::ACCOUNT_TYPES[$state] ?? $state),
                TextColumn::make('current_balance')->label('Baki Semasa (RM)')->money('MYR')->sortable()
                    ->color(fn($state) => $state < 0 ? 'danger' : 'success')->weight('bold'),
                TextColumn::make('balance_updated_at')->label('Dikemas kini')->since(),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('refresh_balance')
                    ->label('Semak Baki')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->action(function (GovernmentBankAccount $record) {
                        try {
                            app(BudgetService::class)->refreshBankBalance($record);
                            Notification::make()
                                ->title('Baki dikemas kini: RM ' . number_format($record->fresh()->current_balance, 2))
                                ->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),
            ])
            ->defaultSort('account_name');
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListGovernmentBankAccounts::route('/'),
            'create' => Pages\CreateGovernmentBankAccount::route('/create'),
            'edit'   => Pages\EditGovernmentBankAccount::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('company_id', Auth::user()?->company_id ?? 1);
    }
}

