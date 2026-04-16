<?php

namespace App\Filament\App\Resources\JournalResource\Pages;

use App\Filament\App\Resources\JournalResource;
use Filament\Resources\Pages\EditRecord;

class EditJournal extends EditRecord
{
    protected static string $resource = JournalResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $data;
    }

    public function hasUnsavedDataChangesAlert(): bool
    {
        return $this->record->isDraft();
    }

    protected function getHeaderActions(): array
    {
        // Kalau bukan draft — no save button
        if (!$this->record->isDraft()) {
            return [];
        }

        return parent::getHeaderActions();
    }

    protected function getFormActions(): array
    {
        // Kalau bukan draft — no save/cancel buttons
        if (!$this->record->isDraft()) {
            return [];
        }

        return parent::getFormActions();
    }
}