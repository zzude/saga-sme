<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ReportType: string implements HasLabel
{
    case BalanceSheet = 'balance_sheet';
    case ProfitLoss   = 'profit_loss';

    public function getLabel(): string
    {
        return match ($this) {
            ReportType::BalanceSheet => 'Balance Sheet',
            ReportType::ProfitLoss   => 'Profit & Loss',
        };
    }

    /** Auto-derive from AccountType */
    public static function fromAccountType(AccountType $type): self
    {
        return match ($type) {
            AccountType::Asset,
            AccountType::Liability,
            AccountType::Equity  => self::BalanceSheet,
            AccountType::Revenue,
            AccountType::Expense => self::ProfitLoss,
        };
    }
}