<?php

namespace App\Filament\App\Resources\JournalResource\Pages;

use App\Filament\App\Resources\JournalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListJournals extends ListRecords
{
    protected static string $resource = JournalResource::class;

    protected function getHeaderActions(): array
    {
        if (!Auth::user()?->hasAnyRole(['super_admin', 'admin', 'user'])) {
            return [];
        }
        return [CreateAction::make()];
    }
 
}