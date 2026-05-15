<?php

namespace App\Filament\Resources\GovernmentBankAccountResource\Pages;

use App\Filament\Resources\GovernmentBankAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;


class CreateGovernmentBankAccount extends CreateRecord
{
    protected static string $resource = GovernmentBankAccountResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = Auth::user()?->company_id ?? 1;
        return $data;
    }
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
}