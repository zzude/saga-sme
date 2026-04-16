<?php

namespace App\Filament\App\Resources\JournalResource\Pages;

use App\Filament\App\Resources\JournalResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\JournalHeader;

class CreateJournal extends CreateRecord
{
    protected static string $resource = JournalResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by']    = auth()->id();
        $data['reference_no']  = $this->generateReferenceNo();

        return $data;
    }

    private function generateReferenceNo(): string
    {
        $year   = now()->format('Y');
        $month  = now()->format('m');
        $count  = JournalHeader::withoutGlobalScopes()
                    ->where('company_id', auth()->user()->company_id)
                    ->whereYear('created_at', $year)
                    ->whereMonth('created_at', $month)
                    ->count() + 1;

        return 'JV-' . $year . $month . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}