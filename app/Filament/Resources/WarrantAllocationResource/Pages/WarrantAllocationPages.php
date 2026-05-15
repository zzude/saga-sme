<?php
// ─── CreateWarrantAllocation.php ─────────────────────────────────
namespace App\Filament\Resources\WarrantAllocationResource\Pages;

use App\Filament\Resources\WarrantAllocationResource;
use App\Models\WarrantAllocation;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateWarrantAllocation extends CreateRecord
{
    protected static string $resource = WarrantAllocationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = Auth::user()?->company_id ?? 1;
        $data['issued_by']  = Auth::id();

        if (empty($data['warrant_no'])) {
            $budget = \App\Models\AnnualBudget::find($data['annual_budget_id']);
            $data['warrant_no'] = WarrantAllocation::generateWarrantNo(
                $data['company_id'],
                $budget?->financial_year ?? now()->year
            );
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

// ─── EditWarrantAllocation.php ────────────────────────────────────
namespace App\Filament\Resources\WarrantAllocationResource\Pages;

use App\Filament\Resources\WarrantAllocationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWarrantAllocation extends EditRecord
{
    protected static string $resource = WarrantAllocationResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

// ─── ViewWarrantAllocation.php ────────────────────────────────────
namespace App\Filament\Resources\WarrantAllocationResource\Pages;

use App\Filament\Resources\WarrantAllocationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
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

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
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
