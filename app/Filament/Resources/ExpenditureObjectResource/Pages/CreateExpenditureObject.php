<?php
namespace App\Filament\Resources\ExpenditureObjectResource\Pages;
use App\Filament\Resources\ExpenditureObjectResource;
use Filament\Resources\Pages\CreateRecord;
class CreateExpenditureObject extends CreateRecord
{
    protected static string $resource = ExpenditureObjectResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = auth()->user()->company_id;
        return $data;
    }
}
