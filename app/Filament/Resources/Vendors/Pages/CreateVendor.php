<?php

namespace App\Filament\Resources\Vendors\Pages;

use App\Filament\Resources\Vendors\VendorResource;
use App\Models\Vendor;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateVendor extends CreateRecord
{
    protected static string $resource = VendorResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = Auth::user()->company_id;

        if (empty($data['vendor_code'])) {
            $latest = Vendor::where('company_id', $data['company_id'])
                ->orderByDesc('id')
                ->first();

            $nextNo = $latest
                ? (int) substr($latest->vendor_code, -4) + 1
                : 1;

            $data['vendor_code'] = 'VEND-' . str_pad($nextNo, 4, '0', STR_PAD_LEFT);
        }

        return $data;
    }
}