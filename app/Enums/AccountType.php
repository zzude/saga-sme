<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AccountType: string implements HasColor, HasLabel
{
    case Asset     = 'asset';
    case Liability = 'liability';
    case Equity    = 'equity';
    case Revenue   = 'revenue';
    case Expense   = 'expense';

    public function getLabel(): string
    {
        return match ($this) {
            AccountType::Asset     => 'Asset',
            AccountType::Liability => 'Liability',
            AccountType::Equity    => 'Equity',
            AccountType::Revenue   => 'Revenue',
            AccountType::Expense   => 'Expense',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            AccountType::Asset     => 'info',
            AccountType::Liability => 'warning',
            AccountType::Equity    => 'primary',
            AccountType::Revenue   => 'success',
            AccountType::Expense   => 'danger',
        };
    }

    /**
     * Normal balance: debit increases Assets/Expenses, credit increases Liabilities/Equity/Revenue.
     */
    public function normalBalance(): string
    {
        return match ($this) {
            AccountType::Asset,
            AccountType::Expense   => 'debit',
            AccountType::Liability,
            AccountType::Equity,
            AccountType::Revenue   => 'credit',
        };
    }
}
