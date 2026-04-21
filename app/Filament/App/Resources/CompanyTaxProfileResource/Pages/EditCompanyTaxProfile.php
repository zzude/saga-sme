<?php
namespace App\Filament\App\Resources\CompanyTaxProfileResource\Pages;
use App\Filament\App\Resources\CompanyTaxProfileResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
class EditCompanyTaxProfile extends EditRecord
{
    protected static string $resource = CompanyTaxProfileResource::class;
    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}