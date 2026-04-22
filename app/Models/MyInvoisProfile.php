<?php

namespace App\Models;

use App\Traits\HasCompanyScope;
use App\Traits\LogsActivityTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MyInvoisProfile extends Model
{
    use HasCompanyScope, LogsActivityTrait;

    protected $fillable = [
        "company_id",
        "mode",
        "environment",
        "client_id",
        "client_secret",
        "tin",
        "branch_code",
        "access_token",
        "token_expires_at",
        "is_active",
    ];

    protected function casts(): array
    {
        return [
            "token_expires_at" => "datetime",
            "is_active"        => "boolean",
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isTokenValid(): bool
    {
        return $this->access_token && $this->token_expires_at?->isFuture();
    }

    public function getBaseUrl(): string
    {
        return $this->environment === "production"
            ? "https://api.myinvois.hasil.gov.my"
            : "https://preprod-api.myinvois.hasil.gov.my";
    }
}
