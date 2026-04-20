<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->visible(fn () => $this->record->status === 'draft'),
        ];
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);

        if (!in_array($this->record->status, ['draft'])) {
            Notification::make()
                ->title('Cannot edit — invoice is ' . $this->record->status)
                ->danger()
                ->send();

            $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
        }
    }    

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Block edit if not draft
        if (!in_array($this->record->status, ['draft'])) {
            Notification::make()
                ->title('Cannot edit — invoice is ' . $this->record->status)
                ->danger()
                ->send();

            $this->halt();
        }

        return $data;
    }
}