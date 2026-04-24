<?php

namespace App\Enums;

enum CompanyStatus: string
{
    case Draft     = 'draft';
    case Active    = 'active';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match($this) {
            self::Draft     => 'Draf',
            self::Active    => 'Aktif',
            self::Suspended => 'Digantung',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Draft     => 'warning',
            self::Active    => 'success',
            self::Suspended => 'danger',
        };
    }
}
