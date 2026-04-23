<?php

namespace App\Enums;

enum EInvoiceStatus: string
{
    case Draft      = 'draft';
    case Submitted  = 'submitted';
    case Processing = 'processing';
    case Valid      = 'valid';
    case Rejected   = 'rejected';
    case Cancelled  = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::Draft      => 'Draft',
            self::Submitted  => 'Submitted',
            self::Processing => 'Processing',
            self::Valid      => 'Valid',
            self::Rejected   => 'Rejected',
            self::Cancelled  => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Draft      => 'gray',
            self::Submitted  => 'warning',
            self::Processing => 'info',
            self::Valid      => 'success',
            self::Rejected   => 'danger',
            self::Cancelled  => 'gray',
        };
    }

    public function canResubmit(): bool
    {
        return $this === self::Rejected;
    }

    public function canCancel(): bool
    {
        return $this === self::Valid;
    }

    public function canCheckStatus(): bool
    {
        return in_array($this, [self::Submitted, self::Processing]);
    }
}