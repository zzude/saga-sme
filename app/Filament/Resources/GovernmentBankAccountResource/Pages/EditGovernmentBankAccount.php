<?php

namespace App\Filament\Resources\GovernmentBankAccountResource\Pages;

use App\Filament\Resources\GovernmentBankAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;



class EditGovernmentBankAccount extends EditRecord
{
    protected static string $resource = GovernmentBankAccountResource::class;
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
}