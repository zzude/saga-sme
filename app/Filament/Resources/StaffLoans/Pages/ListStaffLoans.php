<?php

namespace App\Filament\Resources\StaffLoans\Pages;

use App\Filament\Resources\StaffLoans\StaffLoanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStaffLoans extends ListRecords
{
    protected static string $resource = StaffLoanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
