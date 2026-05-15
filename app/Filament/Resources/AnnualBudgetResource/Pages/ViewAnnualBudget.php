<?php

namespace App\Filament\Resources\AnnualBudgetResource\Pages;

use App\Filament\Resources\AnnualBudgetResource;
use App\Models\AnnualBudget;
use App\Models\BudgetItem;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;

class ViewAnnualBudget extends ViewRecord
{
    protected static string $resource = AnnualBudgetResource::class;

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
            Section::make('Maklumat Bajet')
                ->columns(3)
                ->schema([
                    TextEntry::make('budget_no')->label('No. Bajet')->weight('bold'),
                    TextEntry::make('financial_year')->label('Tahun Kewangan'),
                    TextEntry::make('status')
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
                        ->formatStateUsing(fn($state) => AnnualBudget::statuses()[$state] ?? $state),
                    TextEntry::make('title')->label('Tajuk')->columnSpan(3),
                    TextEntry::make('description')->label('Keterangan')->columnSpan(3),
                    TextEntry::make('effective_date')->label('Kuatkuasa')->date('d/m/Y'),
                    TextEntry::make('expiry_date')->label('Tamat')->date('d/m/Y'),
                ]),

            Section::make('Ringkasan Kewangan')
                ->columns(3)
                ->schema([
                    TextEntry::make('total_amount')->label('Jumlah Bajet')->money('MYR')->weight('bold'),
                    TextEntry::make('allocated_amount')->label('Diperuntukkan')->money('MYR'),
                    TextEntry::make('balance_amount')->label('Baki')->money('MYR')
                        ->color(fn($state) => $state < 0 ? 'danger' : 'success'),
                ]),

            Section::make('Item Bajet')
                ->schema([
                    RepeatableEntry::make('budgetItems')
                        ->label('')
                        ->schema([
                            TextEntry::make('description')->label('Penerangan'),
                            TextEntry::make('object_class')
                                ->label('Kelas Objek')
                                ->formatStateUsing(fn($state) => BudgetItem::OBJECT_CLASSES[$state] ?? $state),
                            TextEntry::make('original_amount')->label('Asal (RM)')->money('MYR'),
                            TextEntry::make('revised_amount')->label('Disemak (RM)')->money('MYR'),
                            TextEntry::make('allocated_amount')->label('Diperuntukkan (RM)')->money('MYR'),
                            TextEntry::make('balance_amount')->label('Baki (RM)')->money('MYR'),
                        ])
                        ->columns(6),
                ]),
        ]);
    }
}
