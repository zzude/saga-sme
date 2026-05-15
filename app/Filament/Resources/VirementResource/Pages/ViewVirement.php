<?php

namespace App\Filament\Resources\VirementResource\Pages;

use App\Filament\Resources\VirementResource;
use App\Models\Virement;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;

class ViewVirement extends ViewRecord
{
    protected static string $resource = VirementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn() => $this->record->isEditable()),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Maklumat Virement')
                ->columns(3)
                ->schema([
                    TextEntry::make('virement_no')->label('No. Virement')->weight('bold'),
                    TextEntry::make('annualBudget.title')->label('Bajet Tahunan'),
                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->color(fn($state) => match ($state) {
                            'draft'            => 'gray',
                            'pending_approval' => 'warning',
                            'approved'         => 'info',
                            'rejected'         => 'danger',
                            'posted'           => 'success',
                            default            => 'gray',
                        })
                        ->formatStateUsing(fn($state) => Virement::statuses()[$state] ?? $state),
                    TextEntry::make('title')->label('Tajuk')->columnSpan(3),
                    TextEntry::make('justification')->label('Justifikasi')->columnSpan(3),
                    TextEntry::make('virement_date')->label('Tarikh')->date('d/m/Y'),
                    TextEntry::make('total_amount')->label('Jumlah (RM)')->money('MYR'),
                    TextEntry::make('approval_reference')->label('Rujukan Kelulusan'),
                    TextEntry::make('approvedBy.name')->label('Diluluskan Oleh'),
                    TextEntry::make('approved_at')->label('Tarikh Lulus')->dateTime('d/m/Y H:i'),
                    TextEntry::make('approval_notes')->label('Nota Kelulusan')->columnSpan(3),
                ]),

            Section::make('Pindahan DARI (FROM)')
                ->schema([
                    RepeatableEntry::make('fromItems')
                        ->label('')
                        ->schema([
                            TextEntry::make('budgetItem.description')->label('Item Bajet'),
                            TextEntry::make('amount')->label('Amaun (RM)')->money('MYR'),
                            TextEntry::make('notes')->label('Nota'),
                        ])
                        ->columns(3),
                ]),

            Section::make('Pindahan KE (TO)')
                ->schema([
                    RepeatableEntry::make('toItems')
                        ->label('')
                        ->schema([
                            TextEntry::make('budgetItem.description')->label('Item Bajet'),
                            TextEntry::make('amount')->label('Amaun (RM)')->money('MYR'),
                            TextEntry::make('notes')->label('Nota'),
                        ])
                        ->columns(3),
                ]),
        ]);
    }
}
