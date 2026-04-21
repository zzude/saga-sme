<?php
namespace App\Filament\App\Resources\CompanyTaxProfileResource\Pages;
use App\Filament\App\Resources\CompanyTaxProfileResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
class ListCompanyTaxProfiles extends ListRecords
{
    protected static string $resource = CompanyTaxProfileResource::class;
    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}