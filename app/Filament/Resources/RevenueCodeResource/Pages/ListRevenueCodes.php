<?php
namespace App\Filament\Resources\RevenueCodeResource\Pages;
use App\Filament\Resources\RevenueCodeResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;
class ListRevenueCodes extends ListRecords
{
    protected static string $resource = RevenueCodeResource::class;
    protected function getHeaderActions(): array { return [CreateAction::make()]; }
}
