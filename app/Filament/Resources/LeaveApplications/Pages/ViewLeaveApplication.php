<?php

namespace App\Filament\Resources\LeaveApplications\Pages;

use App\Filament\Resources\LeaveApplications\LeaveApplicationResource;
use App\Models\LeaveBalance;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewLeaveApplication extends ViewRecord
{
    protected static string $resource = LeaveApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Approve')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->visible(fn() => $this->record->status === 'draft')
                ->form([
                    Textarea::make('notes')
                        ->label('Catatan (optional)')
                        ->rows(2),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'status'        => 'approved',
                        'approved_by'   => Auth::id(),
                        'approved_date' => now(),
                        'notes'         => $data['notes'] ?? null,
                    ]);

                    // Update leave balance
                    $balance = LeaveBalance::firstOrCreate(
                        [
                            'company_id'    => $this->record->company_id,
                            'employee_id'   => $this->record->employee_id,
                            'leave_type_id' => $this->record->leave_type_id,
                            'year'          => $this->record->start_date->year,
                        ],
                        [
                            'entitled_days' => $this->record->leaveType->days_per_year,
                            'used_days'     => 0,
                            'balance_days'  => $this->record->leaveType->days_per_year,
                        ]
                    );

                    $balance->increment('used_days', $this->record->total_days);
                    $balance->decrement('balance_days', $this->record->total_days);

                    Notification::make()->title('Permohonan cuti diluluskan!')->success()->send();
                    $this->refreshFormData(['status', 'approved_by', 'approved_date', 'notes']);
                }),

            Action::make('reject')
                ->label('Tolak')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->visible(fn() => $this->record->status === 'draft')
                ->form([
                    Textarea::make('notes')
                        ->label('Sebab Penolakan')
                        ->required()
                        ->rows(2),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'status' => 'rejected',
                        'notes'  => $data['notes'],
                    ]);
                    Notification::make()->title('Permohonan cuti ditolak.')->warning()->send();
                    $this->refreshFormData(['status', 'notes']);
                }),

            Action::make('cancel')
                ->label('Batal')
                ->color('gray')
                ->icon('heroicon-o-x-mark')
                ->visible(fn() => in_array($this->record->status, ['draft', 'approved']))
                ->requiresConfirmation()
                ->action(function () {
                    // Reverse balance if was approved
                    if ($this->record->status === 'approved') {
                        $balance = LeaveBalance::where([
                            'company_id'    => $this->record->company_id,
                            'employee_id'   => $this->record->employee_id,
                            'leave_type_id' => $this->record->leave_type_id,
                            'year'          => $this->record->start_date->year,
                        ])->first();

                        if ($balance) {
                            $balance->decrement('used_days', $this->record->total_days);
                            $balance->increment('balance_days', $this->record->total_days);
                        }
                    }

                    $this->record->update(['status' => 'cancelled']);
                    Notification::make()->title('Permohonan dibatalkan.')->warning()->send();
                    $this->refreshFormData(['status']);
                }),
        ];
    }
}
