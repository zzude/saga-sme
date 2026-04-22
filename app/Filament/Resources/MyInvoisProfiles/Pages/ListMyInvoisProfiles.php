<?php

namespace App\Filament\Resources\MyInvoisProfiles\Pages;

use App\Filament\Resources\MyInvoisProfiles\MyInvoisProfileResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMyInvoisProfiles extends ListRecords
{
    protected static string $resource = MyInvoisProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
