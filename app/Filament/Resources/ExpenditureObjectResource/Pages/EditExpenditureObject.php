<?php
namespace App\Filament\Resources\ExpenditureObjectResource\Pages;
use App\Filament\Resources\ExpenditureObjectResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\DeleteAction;
class EditExpenditureObject extends EditRecord
{
    protected static string $resource = ExpenditureObjectResource::class;
    protected function getHeaderActions(): array { return [DeleteAction::make()]; }
}
