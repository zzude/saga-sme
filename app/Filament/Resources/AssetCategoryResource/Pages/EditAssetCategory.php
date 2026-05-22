<?php
namespace App\Filament\Resources\AssetCategoryResource\Pages;
use App\Filament\Resources\AssetCategoryResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\DeleteAction;
class EditAssetCategory extends EditRecord
{
    protected static string $resource = AssetCategoryResource::class;
    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
