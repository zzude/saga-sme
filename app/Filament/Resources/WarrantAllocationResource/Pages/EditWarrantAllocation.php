<?php

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