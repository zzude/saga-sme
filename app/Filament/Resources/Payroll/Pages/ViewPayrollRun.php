<?php

namespace App\Filament\Resources\Payroll\Pages;

use App\Filament\Resources\Payroll\PayrollRunResource;
use App\Models\PayrollLine;
use App\Services\PayrollService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\URL;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewPayrollRun extends ViewRecord
{
    protected static string $resource = PayrollRunResource::class;

    protected function getHeaderActions(): array
    {
        return [

            // ── Generate Lines ────────────────────────────────────────────
            Action::make('generateLines')
                ->label('Generate Lines')
                ->icon('heroicon-o-cog')
                ->color('info')
                ->visible(fn () => $this->record->isDraft())
                ->requiresConfirmation()
                ->modalHeading('Generate Payroll Lines')
                ->modalDescription('This will pull all active employees and calculate their salary deductions. Continue?')
                ->action(function () {
                    try {
                        $service = app(PayrollService::class);
                        $count   = $service->generateLines($this->record);

                        Notification::make()
                            ->title("{$count} payroll lines generated successfully!")
                            ->success()
                            ->send();

                        $this->refreshFormData(['status']);

                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            // ── Approve ───────────────────────────────────────────────────
            Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('warning')
                ->visible(fn () => $this->record->isDraft())
                ->requiresConfirmation()
                ->modalHeading('Approve Payroll Run')
                ->modalDescription('Approve this payroll run for GL posting. This cannot be undone.')
                ->action(function () {
                    try {
                        $service = app(PayrollService::class);
                        $service->approve($this->record);

                        Notification::make()
                            ->title('Payroll run approved!')
                            ->success()
                            ->send();

                        $this->refreshFormData(['status', 'approved_at']);

                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            // ── Post to GL ────────────────────────────────────────────────
            Action::make('post')
                ->label('Post to GL')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->visible(fn () => $this->record->isApproved())
                ->requiresConfirmation()
                ->modalHeading('Post Payroll to General Ledger')
                ->modalDescription('This will create journal entries for this payroll run. Continue?')
                ->action(function () {
                    try {
                        $service = app(PayrollService::class);
                        $service->post($this->record);

                        Notification::make()
                            ->title('Payroll posted to GL successfully!')
                            ->success()
                            ->send();

                        $this->refreshFormData(['status', 'posted_at', 'journal_header_id']);

                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            // ── Print All Payslips ─────────────────────────────────────
            Action::make('printPayslips')
                ->label('Print All Payslips')
                ->icon('heroicon-o-printer')
                ->color('info')
                ->visible(fn () => in_array($this->record->status, ['approved', 'posted']))
                ->url(fn () => route('payroll.payslip.all', $this->record))
                ->openUrlInNewTab(),

        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Payroll Run Details')
                ->columns(3)
                ->schema([
                    TextEntry::make('reference_no')->label('Reference No'),
                    TextEntry::make('payrollPeriod.name')->label('Period'),
                    TextEntry::make('status')
                        ->badge()
                        ->color(fn (string $state) => match($state) {
                            'draft'    => 'gray',
                            'approved' => 'warning',
                            'posted'   => 'success',
                            'locked'   => 'info',
                            default    => 'gray',
                        }),
                    TextEntry::make('posted_at')->dateTime()->placeholder('-'),
                    TextEntry::make('approvedBy.name')->label('Approved By')->placeholder('-'),
                    TextEntry::make('journal.reference_no')->label('Journal Ref')->placeholder('-'),
                ]),

            Section::make('Payroll Summary')
                ->columns(4)
                ->schema([
                    TextEntry::make('total_gross')
                        ->label('Total Gross')->money('MYR'),
                    TextEntry::make('total_employee_deduction')
                        ->label('Total EE Deduction')->money('MYR'),
                    TextEntry::make('total_net_salary')
                        ->label('Total Net Salary')->money('MYR')->color('success'),
                    TextEntry::make('total_employer_cost')
                        ->label('Total Employer Cost')->money('MYR')->color('danger'),
                    TextEntry::make('total_kwsp')->label('Total EPF')->money('MYR'),
                    TextEntry::make('total_socso')->label('Total SOCSO')->money('MYR'),
                    TextEntry::make('total_eis')->label('Total EIS')->money('MYR'),
                    TextEntry::make('total_pcb')->label('Total PCB')->money('MYR'),
                ]),

            Section::make('Payroll Lines')
                ->schema([
                    \Filament\Infolists\Components\RepeatableEntry::make('lines')
                        ->schema([
                            TextEntry::make('employee.name')->label('Employee'),
                            TextEntry::make('employee.employee_no')->label('No.'),
                            TextEntry::make('gross_salary')->money('MYR')->label('Gross'),
                            TextEntry::make('total_employee_deduction')->money('MYR')->label('EE Deduct'),
                            TextEntry::make('net_salary')->money('MYR')->label('Net')->color('success'),
                            TextEntry::make('total_employer_cost')->money('MYR')->label('ER Cost'),
                        ])
                        ->columns(6),
                ]),
        ]);
    }
}
