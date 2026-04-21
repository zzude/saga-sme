<?php

namespace App\Filament\App\Pages;

use App\Models\AccountingPeriod;
use App\Services\ProfitLossService;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;

class ProfitLossPage extends Page implements HasForms
{
    use InteractsWithForms;

    public static function getNavigationIcon(): string { return 'heroicon-o-chart-bar'; }
    public static function getNavigationLabel(): string { return 'Profit & Loss'; }
    public static function getNavigationGroup(): ?string { return 'Reports'; }
    public static function getNavigationSort(): ?int { return 2; }

    protected string $view = 'filament.app.pages.profit-loss';

    public ?int $period_id = null;
    public array $result = [];

    public function mount(): void { $this->period_id = null; }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportPdf')
                ->label('Export PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn () => $this->exportPdf()),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('period_id')
                ->label('Accounting Period')
                ->options(
                    AccountingPeriod::where('company_id', Auth::user()->company_id)
                        ->orderByDesc('start_date')
                        ->pluck('name', 'id')
                )
                ->placeholder('— All Periods —')
                ->live()
                ->afterStateUpdated(fn () => $this->generate()),
        ])->columns(3);
    }

    public function generate(): void
    {
        $this->result = (new ProfitLossService())->generate(
            companyId: Auth::user()->company_id,
            periodId:  $this->period_id,
        );
    }

    public function exportPdf(): \Symfony\Component\HttpFoundation\Response
    {
        $result = (new ProfitLossService())->generate(
            companyId: Auth::user()->company_id,
            periodId:  $this->period_id,
        );

        $period = $this->period_id
            ? \App\Models\AccountingPeriod::find($this->period_id)?->name
            : 'All Periods';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.profit-loss-pdf', [
            'result'      => $result,
            'companyName' => Auth::user()->company->name ?? 'SAGA SME',
            'periodName'  => $period,
        ])->setPaper('a4', 'portrait')
          ->set_option('margin_top', '8mm')
          ->set_option('margin_bottom', '8mm')
          ->set_option('margin_left', '8mm')
          ->set_option('margin_right', '8mm');

        return response()->streamDownload(
            fn () => print($pdf->output()),
            'profit-loss-' . now()->format('Ymd-His') . '.pdf'
        );
    }
}
