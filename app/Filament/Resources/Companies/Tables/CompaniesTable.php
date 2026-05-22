<?php

namespace App\Filament\Resources\Companies\Tables;

use App\Models\Plan;
use App\Services\PlanService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CompaniesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('owner.name')
                    ->label('Owner')
                    ->searchable(),
                TextColumn::make('plan.name')
                    ->label('Plan')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Free'       => 'gray',
                        'Pro'        => 'info',
                        'Enterprise' => 'success',
                        default      => 'gray',
                    })
                    ->placeholder('No Plan'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state): string => match (is_string($state) ? $state : $state->value) {
                        'active'    => 'success',
                        'draft'     => 'gray',
                        'suspended' => 'danger',
                        default     => 'gray',
                    }),
                TextColumn::make('onboarding_completed_at')
                    ->label('Onboarded')
                    ->dateTime()
                    ->placeholder('Pending')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('plan_id')
                    ->label('Plan')
                    ->options(fn () => Plan::pluck('name', 'id')),
            ])
            ->recordActions([
                // Assign Plan Action
                Action::make('assign_plan')
                    ->label('Assign Plan')
                    ->icon('heroicon-o-credit-card')
                    ->color('warning')
                    ->form([
                        Select::make('plan_id')
                            ->label('Plan')
                            ->options(fn () => Plan::where('is_active', true)
                                ->orderBy('sort_order')
                                ->pluck('name', 'id'))
                            ->required(),
                        Textarea::make('notes')
                            ->label('Notes (optional)')
                            ->rows(2)
                            ->placeholder('e.g. Upgraded by admin — promo May 2026'),
                    ])
                    ->action(function ($record, array $data) {
                        $plan = Plan::find($data['plan_id']);
                        app(PlanService::class)->assignPlan(
                            $record,
                            $plan,
                            $data['notes'] ?? null
                        );
                        Notification::make()
                            ->title('Plan assigned!')
                            ->body($record->name . ' → ' . $plan->name)
                            ->success()
                            ->send();
                    }),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
