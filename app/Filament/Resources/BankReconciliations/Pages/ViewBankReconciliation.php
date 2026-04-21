<?php

namespace App\Filament\Resources\BankReconciliations\Pages;

use App\Filament\Resources\BankReconciliations\BankReconciliationResource;
use App\Services\BankReconciliationService;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewBankReconciliation extends ViewRecord
{
    protected static string $resource = BankReconciliationResource::class;

    public array $glLines = [];
    public array $clearedIds = [];
    public float $clearedBalance = 0;
    public float $difference = 0;

    protected string $view = 'filament.resources.bank-reconciliations.pages.view-bank-reconciliation';

    public function mount(int|string $record): void
    {
        parent::mount($record);
        $this->loadReconciliationData();
    }

    public function loadReconciliationData(): void
    {
        $service = new BankReconciliationService();
        $lines = $service->getGlLines($this->record);

        $this->clearedIds     = $service->getClearedIds($this->record);
        $this->clearedBalance = $service->clearedBalance($this->record);
        $this->difference     = (float) $this->record->statement_balance - $this->clearedBalance;

        $this->glLines = $lines->map(fn ($line) => [
            'id'           => $line->id,
            'date' => \Carbon\Carbon::parse($line->date)->format('d/m/Y'),
            'reference_no' => $line->reference_no,
            'description'  => $line->description,
            'debit'        => (float) $line->debit,
            'credit'       => (float) $line->credit,
            'cleared'      => in_array($line->id, $this->clearedIds),
        ])->toArray();
    }

    public function toggleLine(int $lineId): void
    {
        if ($this->record->status === 'locked') {
            Notification::make()->title('Reconciliation is locked.')->warning()->send();
            return;
        }

        $service = new BankReconciliationService();
        $service->toggleItem($this->record, $lineId);
        $this->loadReconciliationData();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('complete')
                ->label('Complete Reconciliation')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn () => in_array($this->record->status, ['draft', 'in_progress']))
                ->requiresConfirmation()
                ->modalHeading('Complete Reconciliation')
                ->modalDescription('Difference must be MYR 0.00 to complete. Continue?')
                ->action(function () {
                    try {
                        $service = new BankReconciliationService();
                        $service->complete($this->record);
                        Notification::make()->title('Reconciliation completed!')->success()->send();
                        $this->loadReconciliationData();
                        $this->refreshFormData(['status']);
                    } catch (\Exception $e) {
                        Notification::make()->title('Error: ' . $e->getMessage())->danger()->send();
                    }
                }),

            EditAction::make()
                ->visible(fn () => $this->record->status !== 'locked'),
        ];
    }
}