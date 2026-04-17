<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\Invoice;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = Auth::user()->company_id;
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();
        $data['balance_due'] = $data['total'] ?? 0;

        if (empty($data['invoice_no'])) {
            $year   = now()->format('Y');
            $latest = Invoice::where('company_id', $data['company_id'])
                ->whereYear('created_at', $year)
                ->orderByDesc('id')
                ->first();

            $nextNo = $latest
                ? (int) substr($latest->invoice_no, -4) + 1
                : 1;

            $data['invoice_no'] = 'INV-' . $year . '-' . str_pad($nextNo, 4, '0', STR_PAD_LEFT);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->getRecord();
        $record->load('lines');
        $record->subtotal   = $record->lines->sum('amount');
        $record->tax_amount = $record->lines->sum('tax_amount');
        $record->total      = $record->subtotal + $record->tax_amount;
        $record->balance_due = $record->total - $record->paid_amount;
        $record->save();
    }    
}