<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EInvoiceLog extends Model
{
    protected $table = 'einvoice_logs';
    
    protected $fillable = [
        'invoice_id',
        'action',
        'status',
        'http_status',
        'request_payload',
        'response_payload',
        'error_message',
        'submission_uid',
        'uuid',
    ];

    protected $casts = [
        'request_payload'  => 'array',
        'response_payload' => 'array',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}