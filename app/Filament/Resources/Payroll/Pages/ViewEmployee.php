<?php

namespace App\Filament\Resources\Payroll\Pages;

use App\Filament\Resources\Payroll\EmployeeResource;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ViewRecord;

class ViewEmployee extends ViewRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('ea_form')
                ->label('Download EA Form')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->form([
                    Select::make('year')
                        ->label('Tahun Taksiran')
                        ->options(function () {
                            $currentYear = now()->year;
                            $years = [];
                            for ($y = $currentYear; $y >= $currentYear - 5; $y--) {
                                $years[$y] = 'Tahun ' . $y;
                            }
                            return $years;
                        })
                        ->default(now()->year - 1)
                        ->required(),
                ])
                ->url(fn (array $data) => route('payroll.ea-form', [
                    'employee' => $this->record->id,
                    'year'     => $data['year'] ?? now()->year - 1,
                ]))
                ->openUrlInNewTab(),
        ];
    }
}