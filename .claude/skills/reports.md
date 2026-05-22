# Skill: Reports & PDF Generation

> Context for AI assistants working on SAGA SME's reporting module.

---

## 1. REPORT OVERVIEW

| Report | Type | Filter |
|---|---|---|
| Trial Balance | Accounting | Period or date range |
| Profit & Loss | Financial | Period or date range |
| Balance Sheet | Financial | As at date |
| General Ledger | Accounting | Account + date range |
| AR Aging | AR | As at date |
| AP Aging | AP | As at date |
| SST Return | Compliance | Bimonthly period |
| Payroll Summary | HR | Month/year |

---

## 2. PROFIT & LOSS

### Logic
```php
// Income: SUM of posted journal_lines for accounts type = 'income'
$income = JournalLine::join('journals', ...)
    ->join('accounts', ...)
    ->where('accounts.type', 'income')
    ->where('journals.status', 'posted')
    ->whereBetween('journals.date', [$startDate, $endDate])
    ->sum('journal_lines.base_credit')
    - JournalLine::...->sum('journal_lines.base_debit');

// Expense: SUM for type = 'expense' + 'cost_of_sales'
$expense = JournalLine::...
    ->whereIn('accounts.type', ['expense', 'cost_of_sales'])
    ->sum('journal_lines.base_debit')
    - JournalLine::...->sum('journal_lines.base_credit');

$netProfit = $income - $expense;
```

### Report Sections
```
INCOME
  Sales Revenue                    xxx
  Other Income                     xxx
  ─────────────────────────────
  Total Income                     xxx

COST OF SALES
  Cost of Goods Sold               xxx
  ─────────────────────────────
  Total COGS                       xxx
  ─────────────────────────────
  GROSS PROFIT                     xxx

OPERATING EXPENSES
  Salaries                         xxx
  Rent                             xxx
  Utilities                        xxx
  [other expenses]                 xxx
  ─────────────────────────────
  Total Expenses                   xxx
  ─────────────────────────────
  NET PROFIT / (LOSS)              xxx
```

---

## 3. BALANCE SHEET

### Logic
```php
// Balance Sheet = cumulative from inception to report date

// Assets = SUM(base_debit) - SUM(base_credit) for asset accounts
// Liabilities = SUM(base_credit) - SUM(base_debit) for liability accounts
// Equity = SUM(base_credit) - SUM(base_debit) for equity accounts
//        + Net Profit (from P&L up to report date)

// Accounting equation check: Assets = Liabilities + Equity
```

### Report Sections
```
ASSETS
  Current Assets
    Cash at Bank                   xxx
    Accounts Receivable            xxx
    Prepayments                    xxx
  ─────────────────────────────
  Total Current Assets             xxx

  Non-Current Assets
    Fixed Assets (net)             xxx
  ─────────────────────────────
  Total Non-Current Assets         xxx
  ─────────────────────────────
  TOTAL ASSETS                     xxx

LIABILITIES
  Current Liabilities
    Accounts Payable               xxx
    SST Payable                    xxx
  ─────────────────────────────
  Total Current Liabilities        xxx
  ─────────────────────────────
  TOTAL LIABILITIES                xxx

EQUITY
  Paid-up Capital                  xxx
  Retained Earnings                xxx
  Current Year Profit              xxx
  ─────────────────────────────
  TOTAL EQUITY                     xxx
  ─────────────────────────────
  TOTAL LIABILITIES + EQUITY       xxx
```

---

## 4. PDF GENERATION (DomPDF)

### Package
```
barryvdh/laravel-dompdf
```

### ⚠️ Critical: Inline Styles Required
```html
<!-- ✅ CORRECT — DomPDF renders inline styles -->
<table style="width: 100%; border-collapse: collapse; font-family: DejaVu Sans, sans-serif;">
  <tr style="background-color: #f3f4f6;">
    <td style="padding: 8px 12px; border-bottom: 1px solid #e5e7eb; font-weight: bold;">
      Description
    </td>
  </tr>
</table>

<!-- ❌ WRONG — Tailwind classes do NOT work in DomPDF -->
<table class="w-full border-collapse">
  <tr class="bg-gray-100">
    <td class="p-2 font-bold">Description</td>
  </tr>
</table>
```

### PDF Controller Pattern
```php
// routes/web.php
Route::get('/reports/profit-loss/pdf', [ReportController::class, 'plPdf'])
    ->name('reports.pl.pdf')
    ->middleware(['auth']);

// ReportController.php
public function plPdf(Request $request): Response
{
    $data = $this->buildPlData($request);
    
    $pdf = Pdf::loadView('reports.pdf.profit-loss', $data)
        ->setPaper('a4', 'portrait')
        ->setOption(['defaultFont' => 'DejaVu Sans']);
    
    return $pdf->download('profit-loss-' . now()->format('Y-m-d') . '.pdf');
}
```

### Blade PDF Template Structure
```blade
{{-- resources/views/reports/pdf/profit-loss.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        /* Embed all styles here for DomPDF compatibility */
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; }
        .header { text-align: center; margin-bottom: 20px; }
        .company-name { font-size: 16px; font-weight: bold; }
        .report-title { font-size: 13px; }
        table { width: 100%; border-collapse: collapse; }
        .amount { text-align: right; }
        .total-row { font-weight: bold; border-top: 2px solid #000; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $company->name }}</div>
        <div class="report-title">PROFIT & LOSS STATEMENT</div>
        <div>For the period: {{ $periodLabel }}</div>
    </div>
    {{-- Report content --}}
</body>
</html>
```

---

## 5. FILAMENT REPORT PAGES

### Pattern for Report Pages
```php
// In Filament Resource or standalone Page
class ProfitLossReport extends Page
{
    protected static string $view = 'filament.pages.reports.profit-loss';
    protected static ?string $navigationGroup = 'Reports';
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    public static function getNavigationGroup(): ?string
    {
        return 'Reports';
    }

    public ?string $startDate = null;
    public ?string $endDate = null;

    public function mount(): void
    {
        // Default: current month
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate   = now()->endOfMonth()->format('Y-m-d');
    }

    public function getReportData(): array
    {
        return app(ReportService::class)->getProfitLoss(
            auth()->user()->company_id,
            $this->startDate,
            $this->endDate
        );
    }
}
```

---

## 6. NUMBER FORMATTING (MALAYSIAN)

```php
// Currency: RM with 2 decimal places
number_format($amount, 2)    // 12,345.67
'RM ' . number_format($amount, 2)  // RM 12,345.67

// Negative amounts (loss/deficit)
$amount < 0
    ? '(' . number_format(abs($amount), 2) . ')'   // (1,234.56)
    : number_format($amount, 2)

// Date format: Malaysian standard
$date->format('d/m/Y')     // 15/01/2026
```

---

## 7. DEMO DATA

- DemoDataSeeder: Trial Balance DR = CR = 1,874,794.86
- 3 months of payroll seeded (Jan–Mar 2026)
- AR/AP transactions seeded for realistic P&L
