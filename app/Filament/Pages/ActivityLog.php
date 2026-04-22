<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Spatie\Activitylog\Models\Activity;

class ActivityLog extends Page implements HasTable
{
    use InteractsWithTable;

    public static function getNavigationIcon(): string|null { return "heroicon-o-clipboard-document-list"; }
    public static function getNavigationLabel(): string { return "Activity Log"; }
    public static function getNavigationGroup(): ?string { return "System"; }
    public static function getNavigationSort(): ?int { return 99; }
    public function getView(): string { return "filament.pages.activity-log"; }

    public function table(Table $table): Table
    {
        return $table
            ->query(Activity::query()->latest())
            ->columns([
                TextColumn::make("created_at")
                    ->label("Date/Time")
                    ->dateTime("d M Y, H:i:s")
                    ->sortable(),
                TextColumn::make("causer.name")
                    ->label("User")
                    ->default("System"),
                TextColumn::make("event")
                    ->label("Action")
                    ->badge()
                    ->color(fn(string $state) => match($state) {
                        "created" => "success",
                        "updated" => "warning",
                        "deleted" => "danger",
                        default   => "gray",
                    }),
                TextColumn::make("subject_type")
                    ->label("Module")
                    ->formatStateUsing(fn($state) => class_basename($state)),
                TextColumn::make("subject_id")
                    ->label("ID"),
                TextColumn::make("description")
                    ->label("Description"),
            ])
            ->filters([
                SelectFilter::make("event")
                    ->options([
                        "created" => "Created",
                        "updated" => "Updated",
                        "deleted" => "Deleted",
                    ]),
            ])
            ->defaultSort("created_at", "desc")
            ->paginated([25, 50, 100]);
    }
}
