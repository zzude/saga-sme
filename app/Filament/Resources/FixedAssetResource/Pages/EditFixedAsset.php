<?php
namespace App\Filament\Resources\FixedAssetResource\Pages;
use App\Filament\Resources\FixedAssetResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\DeleteAction;

class EditFixedAsset extends EditRecord
{
    protected static string $resource = FixedAssetResource::class;
    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
