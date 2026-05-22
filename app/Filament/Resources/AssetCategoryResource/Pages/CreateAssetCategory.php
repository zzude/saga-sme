<?php
namespace App\Filament\Resources\AssetCategoryResource\Pages;
use App\Filament\Resources\AssetCategoryResource;
use Filament\Resources\Pages\CreateRecord;
class CreateAssetCategory extends CreateRecord
{
    protected static string $resource = AssetCategoryResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = auth()->user()->company_id;
        return $data;
    }
}
