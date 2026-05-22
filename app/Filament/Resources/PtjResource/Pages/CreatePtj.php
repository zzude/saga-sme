<?php
namespace App\Filament\Resources\PtjResource\Pages;
use App\Filament\Resources\PtjResource;
use Filament\Resources\Pages\CreateRecord;
class CreatePtj extends CreateRecord
{
    protected static string $resource = PtjResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = auth()->user()->company_id;
        return $data;
    }
}
