<?php
namespace App\Filament\Resources\RevenueCodeResource\Pages;
use App\Filament\Resources\RevenueCodeResource;
use Filament\Resources\Pages\CreateRecord;
class CreateRevenueCode extends CreateRecord
{
    protected static string $resource = RevenueCodeResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = auth()->user()->company_id;
        return $data;
    }
}
