<?php

namespace App\Filament\Resources\Bills\Pages;

use App\Filament\Resources\Bills\BillResource;
use App\Models\Bill;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateBill extends CreateRecord
{
    protected static string $resource = BillResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = Auth::user()->company_id;
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();
        $data['balance_due'] = $data['total'] ?? 0;

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->getRecord();
        $record->load('lines');
        $record->subtotal    = $record->lines->sum('amount');
        $record->tax_amount  = $record->lines->sum('tax_amount');
        $record->total       = $record->subtotal + $record->tax_amount;
        $record->balance_due = $record->total - $record->paid_amount;
        $record->save();
    }
}