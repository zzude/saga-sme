<?php
namespace App\Filament\Resources\AssetCategoryResource\Pages;
use App\Filament\Resources\AssetCategoryResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;
class ListAssetCategories extends ListRecords
{
    protected static string $resource = AssetCategoryResource::class;
    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
