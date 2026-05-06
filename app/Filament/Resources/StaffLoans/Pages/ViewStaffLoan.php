<?php

namespace App\Filament\Resources\StaffLoans\Pages;

use App\Filament\Resources\StaffLoans\StaffLoanResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewStaffLoan extends ViewRecord
{
    protected static string $resource = StaffLoanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
