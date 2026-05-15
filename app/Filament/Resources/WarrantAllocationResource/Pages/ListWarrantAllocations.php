<?php
// ─── ListWarrantAllocations.php ──────────────────────────────────
namespace App\Filament\Resources\WarrantAllocationResource\Pages;

use App\Filament\Resources\WarrantAllocationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWarrantAllocations extends ListRecords
{
    protected static string $resource = WarrantAllocationResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('+ Waran Baru')];
    }
}