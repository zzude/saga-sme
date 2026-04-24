<?php

namespace App\Services;

use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegistrationService
{
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data) {

            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            $company = Company::create([
                'name'     => $data['company_name'],
                'status'   => CompanyStatus::Draft,
                'owner_id' => $user->id,
            ]);

            // Link user → company
            $user->update(['company_id' => $company->id]);

            // Assign Spatie role
            $user->assignRole('super_admin');

            // Send verification email
            $user->sendEmailVerificationNotification();

            return $user;
        });
    }
}
