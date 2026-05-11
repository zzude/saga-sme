<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Customer;
use App\Services\PlanService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    public function mount(): void
    {
        $company = Auth::user()->currentCompany();
        $planService = app(PlanService::class);

        if (!$planService->canAddCustomer($company)) {
            $usage = $planService->getUsage($company);
            Notification::make()
                ->title('Had pelanggan dicapai')
                ->body('Anda telah mencapai had pelanggan (' . $usage['customers']['used'] . '/' . $usage['customers']['limit'] . '). Naik taraf plan untuk tambah lebih.')
                ->danger()
                ->persistent()
                ->send();

            $this->redirect($this->getResource()::getUrl('index'));
            return;
        }

        parent::mount();
    }

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