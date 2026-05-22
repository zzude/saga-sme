<?php
namespace App\Filament\Resources\RevenueCodeResource\Pages;
use App\Filament\Resources\RevenueCodeResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\DeleteAction;
class EditRevenueCode extends EditRecord
{
    protected static string $resource = RevenueCodeResource::class;
    protected function getHeaderActions(): array { return [DeleteAction::make()]; }
}
