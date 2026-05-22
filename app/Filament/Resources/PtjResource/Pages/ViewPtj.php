<?php
namespace App\Filament\Resources\PtjResource\Pages;
use App\Filament\Resources\PtjResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\EditAction;
class ViewPtj extends ViewRecord
{
    protected static string $resource = PtjResource::class;
    protected function getHeaderActions(): array { return [EditAction::make()]; }
}
