<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Customer;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = Auth::user()->company_id;

        // Auto-generate customer_code if empty
        if (empty($data['customer_code'])) {
            $latest = Customer::where('company_id', $data['company_id'])
                ->orderByDesc('id')
                ->first();

            $nextNo = $latest
                ? (int) substr($latest->customer_code, -4) + 1
                : 1;

            $data['customer_code'] = 'CUST-' . str_pad($nextNo, 4, '0', STR_PAD_LEFT);
        }

        return $data;
    }
}