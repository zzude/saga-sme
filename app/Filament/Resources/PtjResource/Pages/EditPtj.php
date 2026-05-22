<?php
namespace App\Filament\Resources\PtjResource\Pages;
use App\Filament\Resources\PtjResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\DeleteAction;
class EditPtj extends EditRecord
{
    protected static string $resource = PtjResource::class;
    protected function getHeaderActions(): array { return [DeleteAction::make()]; }
}
