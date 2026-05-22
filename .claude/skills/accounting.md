# Skill: Accounting Core

> Context for AI assistants working on SAGA SME's accounting engine.

---

## 1. CHART OF ACCOUNTS (COA)

### Table: `accounts`
```
id, company_id, code, name, type, sub_type,
normal_balance, is_active, parent_id, created_at, updated_at
```

### Account Types
| Type | Normal Balance | Examples |
|---|---|---|
| asset | DR | Cash, AR, Fixed Assets |
| liability | CR | AP, Loans, SST Payable |
| equity | CR | Capital, Retained Earnings |
| income | CR | Sales, Service Revenue |
| expense | DR | Salaries, Rent, Utilities |

### Malaysian Standard COA (92 accounts seeded)
- **1000–1999:** Assets (Current + Fixed)
- **2000–2999:** Liabilities (Current + Long-term)
- **3000–3999:** Equity
- **4000–4999:** Income
- **5000–5999:** Cost of Sales
- **6000–6999:** Operating Expenses
- **7000–7999:** Other Income
- **8000–8999:** Other Expenses

### Key Accounts to Remember
| Code | Name | Notes |
|---|---|---|
| 1100 | Cash at Bank | Main operating account |
| 1200 | Petty Cash | |
| 1300 | Accounts Receivable | AR control account |
| 2100 | Accounts Payable | AP control account |
| 2300 | SST Payable | SST collected |
| 2310 | SST Claimable | SST paid on purchases |
| 3100 | Paid-up Capital | |
| 3200 | Retained Earnings | |
| 4100 | Sales Revenue | |
| 5100 | Cost of Goods Sold | |

---

## 2. ACCOUNTING PERIODS

### Table: `accounting_periods`
```
id, company_id, name, start_date, end_date,
status (open/closed), closed_at, closed_by, created_at, updated_at
```

### Rules
- Journals can only be posted to **open** periods
- Closing a period is **irreversible** — warn user before closing
- Period name convention: `January 2026`, `February 2026`, etc.
- System auto-creates 12 periods on company setup

---

## 3. JOURNAL ENTRY ENGINE

### Tables
```
journals
  id, company_id, journal_no, type, reference, description,
  date, period_id, status (draft/posted), posted_at, posted_by,
  created_by, created_at, updated_at

journal_lines
  id, journal_id, account_id, description,
  debit, credit, currency_code, exchange_rate,
  base_debit, base_credit, created_at, updated_at
```

### Journal Number Format
```
JV-YYYY-NNNNN    e.g. JV-2026-00001
```

### Posting Rules (MUST ENFORCE)
1. `SUM(debit) == SUM(credit)` — validation before post
2. Only `draft` journals can be posted
3. Period must be `open` on journal date
4. Posted journals are **immutable** — use reversal to correct

### Reversal Pattern
```php
// Create reversal journal — swap DR/CR lines
$reversal = Journal::create([...]);
foreach ($original->lines as $line) {
    $reversal->lines()->create([
        'account_id' => $line->account_id,
        'debit'  => $line->credit,   // swapped
        'credit' => $line->debit,    // swapped
        ...
    ]);
}
// Link originals
$original->update(['reversed_by_journal_id' => $reversal->id]);
$reversal->update(['is_reversal' => true, 'reversal_of_journal_id' => $original->id]);
```

### JournalService — Key Methods
```php
JournalService::create(array $data): Journal
JournalService::post(Journal $journal): Journal
JournalService::void(Journal $journal): Journal       // creates reversal
JournalService::reversal(Journal $journal): Journal   // explicit reversal
```

---

## 4. TRIAL BALANCE

- Query: SUM of all posted journal_lines grouped by account_id
- Filter: by period or date range
- Balanced check: `SUM(debit) == SUM(credit)` across all accounts
- Demo data seeded: DR = CR = 1,874,794.86

---

## 5. GENERAL LEDGER

- Per-account view of all posted journal_lines
- Columns: Date | Journal No | Description | DR | CR | Balance (running)
- Filter: account + date range

---

## 6. COMMON CODING PATTERNS

### Validate DR = CR before post
```php
$totalDebit  = $journal->lines->sum('debit');
$totalCredit = $journal->lines->sum('credit');

if (round($totalDebit, 2) !== round($totalCredit, 2)) {
    throw new \Exception('Journal tidak balanced. DR ≠ CR.');
}
```

### Scope by company (multi-tenant)
```php
// All queries MUST include company_id scope
Journal::where('company_id', auth()->user()->company_id)->get();

// Or use global scope via HasCompanyScope trait
use App\Traits\HasCompanyScope;
```

### Period validation
```php
$period = AccountingPeriod::where('company_id', $companyId)
    ->where('start_date', '<=', $date)
    ->where('end_date', '>=', $date)
    ->where('status', 'open')
    ->first();

if (!$period) {
    throw new \Exception('Tiada tempoh perakaunan terbuka untuk tarikh ini.');
}
```

---

## 7. KNOWN ISSUES / GOTCHAS

- `payroll_lines` table (NOT `payroll_items`) — common mistake
- `posted_at` timestamp on journals (NOT `is_posted` boolean)
- Always use `round($amount, 2)` for currency comparisons — float precision
- Multi-currency: use `base_debit`/`base_credit` for MYR reporting, `debit`/`credit` for foreign currency
