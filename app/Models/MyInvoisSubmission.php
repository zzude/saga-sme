<?php

namespace App\Models;

use App\Traits\HasCompanyScope;
use App\Traits\LogsActivityTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MyInvoisSubmission extends Model
{
    use HasCompanyScope, LogsActivityTrait;

    protected $fillable = [
        "company_id",
        "invoice_id",
        "submission_uid",
        "document_uid",
        "long_id",
        "status",
        "invoice_no",
        "ubl_payload",
        "response_payload",
        "error_message",
        "retry_count",
        "submitted_at",
        "validated_at",
    ];

    protected function casts(): array
    {
        return [
            "ubl_payload"      => "array",
            "response_payload" => "array",
            "submitted_at"     => "datetime",
            "validated_at"     => "datetime",
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function isPending(): bool  { return in_array($this->status, ["submitted", "processing"]); }
    public function isValidated(): bool { return $this->status === "validated"; }
    public function isRejected(): bool  { return $this->status === "rejected"; }

    public function getQrUrl(): ?string
    {
        if (!$this->long_id) return null;
        return "https://myinvois.hasil.gov.my/{$this->long_id}/share";
    }
}
