<?php

// ─── ViewWarrantAllocation.php ────────────────────────────────────
namespace App\Filament\Resources\WarrantAllocationResource\Pages;

use App\Filament\Resources\WarrantAllocationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\RepeatableEntry;

class ViewWarrantAllocation extends ViewRecord
{
    protected static string $resource = WarrantAllocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn() => $this->record->status === \App\Models\WarrantAllocation::STATUS_DRAFT),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Maklumat Waran')
                ->columns(3)
                ->schema([
                    TextEntry::make('warrant_no')->label('No. Waran')->weight('bold'),
                    TextEntry::make('annualBudget.title')->label('Bajet Tahunan'),
                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->color(fn($state) => match ($state) {
                            'draft'     => 'gray', 'issued' => 'info',
                            'active'    => 'success', 'exhausted' => 'warning',
                            'cancelled' => 'danger', default => 'gray',
                        })
                        ->formatStateUsing(fn($state) => \App\Models\WarrantAllocation::statuses()[$state] ?? $state),
                    TextEntry::make('title')->label('Tajuk')->columnSpan(3),
                    TextEntry::make('total_amount')->label('Jumlah (RM)')->money('MYR'),
                    TextEntry::make('used_amount')->label('Digunakan (RM)')->money('MYR'),
                    TextEntry::make('balance_amount')->label('Baki (RM)')->money('MYR')
                        ->color(fn($state) => $state <= 0 ? 'danger' : 'success'),
                    TextEntry::make('issue_date')->label('Tarikh')->date('d/m/Y'),
                    TextEntry::make('reference_doc')->label('Rujukan Dok'),
                ]),

            Section::make('Item Waran')
                ->schema([
                    RepeatableEntry::make('warrantItems')
                        ->label('')
                        ->schema([
                            TextEntry::make('budgetItem.description')->label('Item Bajet'),
                            TextEntry::make('warrant_amount')->label('Amaun (RM)')->money('MYR'),
                            TextEntry::make('used_amount')->label('Digunakan (RM)')->money('MYR'),
                            TextEntry::make('balance_amount')->label('Baki (RM)')->money('MYR'),
                        ])
                        ->columns(4),
                ]),
        ]);
    }
}

