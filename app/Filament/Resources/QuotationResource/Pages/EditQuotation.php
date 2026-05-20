<?php

namespace App\Filament\Resources\QuotationResource\Pages;

use App\Filament\Resources\QuotationResource;
use App\Models\Quotation;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;


// =============================================================================
// EDIT
// =============================================================================
class EditQuotation extends EditRecord
{
    protected static string $resource = QuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn () => $this->record->status === 'draft'),
        ];
    }

    protected function beforeSave(): void
    {
        if (!in_array($this->record->status, ['draft'])) {
            Notification::make()
                ->title('Hanya Sebut Harga Draf boleh diedit.')
                ->danger()
                ->send();
            $this->halt();
        }
    }

    protected function afterSave(): void
    {
        // Re-number lines
        $lineNo = 1;
        foreach ($this->record->fresh()->items as $item) {
            $item->update(['line_no' => $lineNo++]);
        }

        // Recalculate totals
        $this->record->fresh()->recalculateTotals();
    }

    protected function getRedirectUrl(): string
    {
        return QuotationResource::getUrl('view', ['record' => $this->record]);
    }
}