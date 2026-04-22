<?php

namespace App\Filament\Resources\MyInvoisProfiles\Pages;

use App\Filament\Resources\MyInvoisProfiles\MyInvoisProfileResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMyInvoisProfile extends EditRecord
{
    protected static string $resource = MyInvoisProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
