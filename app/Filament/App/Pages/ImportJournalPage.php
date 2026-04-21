<?php

namespace App\Filament\App\Pages;

use App\Models\AccountingPeriod;
use App\Services\CsvImportService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class ImportJournalPage extends Page implements HasForms
{
    use InteractsWithForms;

    public static function getNavigationIcon(): string { return 'heroicon-o-document-arrow-up'; }
    public static function getNavigationLabel(): string { return 'Import Journals'; }
    public static function getNavigationGroup(): ?string { return 'Settings'; }
    public static function getNavigationSort(): ?int { return 21; }

    protected string $view = 'filament.app.pages.import-journal';

    public int $period_id = 0;
    public array $file = [];
    public array $preview = [];
    public bool $previewed = false;  

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadTemplate')
                ->label('Download Template')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn () => $this->downloadTemplate()),
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
                ->required(),
            FileUpload::make('file')
                ->label('Upload CSV File')
                ->acceptedFileTypes(['text/csv', 'application/vnd.ms-excel', 'text/plain'])
                ->maxSize(2048)
                ->required(),
        ])->columns(2);
    }

    public function previewImport(): void
    {
        $data = $this->form->getState();
        
        $filePath = is_array($data['file']) ? reset($data['file']) : $data['file'];
        $path = storage_path('app/private/' . $filePath);
        if (!file_exists($path)) {
            $path = storage_path('app/private/livewire-tmp/' . $filePath);
        }
        if (!file_exists($path)) {
            $path = storage_path('app/public/' . $filePath);
        }

        if (!file_exists($path)) {
            Notification::make()->title('File not found.')->danger()->send();
            return;
        }

        $service = new CsvImportService();
        $rows    = $service->parseCsv($path);

        if (empty($rows)) {
            Notification::make()->title('CSV empty or invalid.')->danger()->send();
            return;
        }

        $this->preview   = $service->previewJournals($rows, Auth::user()->company_id);
        $this->period_id = (int) $data['period_id'];
        $this->previewed = true;

        Notification::make()
            ->title("Preview ready — {$this->preview['ok_count']} OK, {$this->preview['err_count']} errors.")
            ->color($this->preview['err_count'] > 0 ? 'warning' : 'success')
            ->send();
    }

    public function commitImport(): void
    {
        if (empty($this->preview) || !$this->preview['can_import']) {
            Notification::make()->title('Fix errors before importing.')->danger()->send();
            return;
        }

        $service = new CsvImportService();
        $result  = $service->commitJournals(
            $this->preview['entries'],
            Auth::user()->company_id,
            $this->period_id
        );

        $this->previewed = false;
        $this->preview   = [];

        Notification::make()
            ->title("Imported {$result['imported']} journal entries successfully!")
            ->success()
            ->send();
    }

    public function downloadErrors(): \Symfony\Component\HttpFoundation\Response
    {
        $service = new CsvImportService();
        $csv     = $service->generateErrorCsv($this->preview['errors'] ?? []);

        return response()->streamDownload(
            fn () => print($csv),
            'journal-import-errors.csv'
        );
    }

    public function downloadTemplate(): \Symfony\Component\HttpFoundation\Response
    {
        $csv  = "reference_no,date,summary_text,account_code,debit,credit,description\n";
        $csv .= "JV-001,2026-04-01,Opening Entry,1110,10000,0,Cash brought in\n";
        $csv .= "JV-001,2026-04-01,Opening Entry,3100,0,10000,Capital contribution\n";
        $csv .= "JV-002,2026-04-02,Office Rent,5200,1500,0,April rent\n";
        $csv .= "JV-002,2026-04-02,Office Rent,1110,0,1500,Cash paid\n";

        return response()->streamDownload(
            fn () => print($csv),
            'journal-import-template.csv'
        );
    }
}