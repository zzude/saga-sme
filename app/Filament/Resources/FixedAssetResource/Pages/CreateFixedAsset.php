<?php
namespace App\Filament\Resources\FixedAssetResource\Pages;
use App\Filament\Resources\FixedAssetResource;
use App\Services\FixedAssetService;
use Filament\Resources\Pages\CreateRecord;

class CreateFixedAsset extends CreateRecord
{
    protected static string $resource = FixedAssetResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return app(FixedAssetService::class)->create($data);
    }
}
