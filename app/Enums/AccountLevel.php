<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AccountLevel: int implements HasColor, HasLabel
{
    case Category = 1;
    case Group    = 2;
    case Account  = 3;

    public function getLabel(): string
    {
        return match ($this) {
            AccountLevel::Category => 'Category',
            AccountLevel::Group    => 'Group',
            AccountLevel::Account  => 'Account',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            AccountLevel::Category => 'gray',
            AccountLevel::Group    => 'info',
            AccountLevel::Account  => 'success',
        };
    }

    /** Only Level 3 (Account) may have transactions posted to it. */
    public function isPostable(): bool
    {
        return $this === AccountLevel::Account;
    }

    /** The level that a child of this level would be. */
    public function childLevel(): ?AccountLevel
    {
        return match ($this) {
            AccountLevel::Category => AccountLevel::Group,
            AccountLevel::Group    => AccountLevel::Account,
            AccountLevel::Account  => null,
        };
    }
}
