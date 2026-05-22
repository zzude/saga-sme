# SAGA SME — AI Development Context

> **For AI assistants (Claude, Copilot, etc.):** Read this file in full before writing any code.
> Last updated: May 2026

---

## 1. PROJECT OVERVIEW

**SAGA SME** is a Malaysian SME accounting system built on Laravel 12 + Filament 5.x.

| Item | Value |
|---|---|
| Full name | SAGA SME — Sistem Akaun Generik Adaptif |
| Current version | v1.0 "Asas" (released May 2026) |
| Repo | github.com/zzude/saga-sme |
| Local path | `C:\laragon6\www\saga-sme` |
| Database | `saga_sme` (MySQL/MariaDB via Laragon 6) |
| DB credentials | root / root |
| PHP | 8.4 |

**Vision:** Sabah/Sarawak SME market — affordable, locally-relevant, e-Invoice ready.

---

## 2. VERSION ROADMAP

| Version | Codename | Status | Scope |
|---|---|---|---|
| v1.0 | "Asas" | ✅ Complete | Core accounting, AR/AP, payroll, multi-currency |
| v1.1 | "Niaga" | 🔲 Planned | Fixed Assets, POS, Inventory |
| v1.2 | "Patuh" | 🔲 Planned | MyInvois live, Billplz live |
| v2.0 | "Maju" | 🔲 Future | Multi-tenant SaaS |

---

## 3. TECH STACK

| Layer | Technology |
|---|---|
| Framework | Laravel 12 |
| Admin UI | Filament 5.x (latest) |
| Database | MySQL / MariaDB |
| PDF | barryvdh/laravel-dompdf |
| RBAC | Spatie Laravel Permission |
| Activity Log | Spatie Activitylog v5 |
| FX Rates | Frankfurter API (free, no key) |
| Local dev | Laragon 6 |
| Version control | GitHub (zzude/saga-sme) |

---

## 4. FILAMENT 5.x CRITICAL RULES

> ⚠️ These rules are non-negotiable. Violating them causes FatalErrors.

### 4.1 Namespaces
```php
// Actions
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Actions\Action;      // for table row actions
use Filament\Tables\Actions\EditAction;  // for table row actions

// Schema / Forms
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;

// Correct form method signature
public function form(Schema $schema): Schema  // NOT Form $form
{
    return $schema->components([...]);         // NOT ->schema([...])
}
```

### 4.2 Navigation — Methods NOT Properties
```php
// ✅ CORRECT
public static function getNavigationGroup(): ?string { return 'Accounting'; }
public static function getNavigationIcon(): string { return 'heroicon-o-document'; }
public static function getNavigationSort(): int { return 1; }

// ❌ WRONG — causes FatalError (strict UnitEnum|string|null typing)
protected static ?string $navigationGroup = 'Accounting';
```

### 4.3 Table Columns with Computed State
```php
// Use getStateUsing() for computed/related data
TextColumn::make('balance')
    ->getStateUsing(fn ($record) => $record->calculateBalance()),
```

---

## 5. SPATIE ACTIVITYLOG V5 RULES

```php
// Correct namespace
use Spatie\Activitylog\Models\Concerns\LogsActivity;

// Correct method (NOT dontSubmitEmptyLogs)
->dontLogEmptyChanges()

// attribute_changes returns Collection, not array
$activity->changes->get('attributes')  // correct
```

---

## 6. RBAC SETUP

| Item | Value |
|---|---|
| Package | Spatie Laravel Permission |
| Gate bypass | `Gate::before` in `AppServiceProvider.php` for `super_admin` |
| Test user | akaun@demo.com / akaun1234 |
| Test role | treasurer |
| Admin user | admin@sagasme.com (super_admin) |

---

## 7. MODULES — v1.0 "ASAS" COMPLETE

### 7.1 Accounting Core
- Chart of Accounts (Malaysian standard, 92 accounts seeded)
- Accounting Periods (monthly, open/close)
- Journal Entry Engine (DR=CR validation, Draft→Posted workflow)
- General Ledger, Trial Balance

### 7.2 AR / AP
- Customer + Sales Invoice (post → AR journal)
- Vendor + Vendor Bill (post → AP journal)
- Payment recording (AR/AP settlement)

### 7.3 Banking
- Bank Reconciliation (match statement vs journals)

### 7.4 Reports
- Profit & Loss (period filter)
- Balance Sheet
- PDF export (DomPDF)
- CSV Import (journal bulk upload)

### 7.5 Compliance
- SST Module (6% service tax, 10% sales tax)
- MyInvois scaffold (LHDN e-Invoice — pending live credentials)
- Billplz payment gateway scaffold (pending sandbox account)

### 7.6 HR / Payroll
- Employee management
- Payroll with full Malaysian statutory: KWSP, SOCSO, EIS, PCB
- Leave Management
- Cash Advance

### 7.7 Multi-Currency
- Base currency: MYR
- Active: USD, SGD
- Auto rate fetch: Frankfurter API
- Schema: `base_*` and `foreign_*` columns on relevant tables

### 7.8 Quotation Module
- Statuses: Draft → Sent → Accepted → Convert to Invoice
- Revision support: QT-YYYY-NNNNN-R1 prefix
- Commit: 65224f6

---

## 8. KEY DATABASE NOTES

```
DB name      : saga_sme
Payroll      : payroll_lines (NOT payroll_items)
             : payroll_periods (separate table)
Invoices     : posted_at column (NOT is_posted boolean)
FX columns   : base_amount, foreign_amount, exchange_rate
```

---

## 9. PENDING ITEMS (v1.0 → v1.1)

| # | Item | Notes |
|---|---|---|
| 1 | Billplz sandbox | Needs Billplz account |
| 2 | MyInvois live | Needs LHDN sandbox credentials |
| 3 | Quotation: qty/unit display | Minor UI bug |
| 4 | Quotation: ringkasan RM 0.00 | Calculation bug |
| 5 | Quotation: PDF route | Route not wired |

---

## 10. DEV WORKFLOW

```
Edit local Laragon → Test → Commit → Push → (no direct VPS edit for SAGA)
```

```bash
# Run locally
cd C:\laragon6\www\saga-sme
php artisan serve        # or via Laragon virtual host

# Common commands
php artisan migrate
php artisan db:seed --class=DemoDataSeeder
php artisan optimize:clear
php artisan filament:assets
```

---

## 11. CODING STANDARDS

1. **One module at a time** — never mix unrelated changes in one commit
2. **Double-entry always** — every financial transaction must DR = CR
3. **Multi-tenant ready** — all models must be scoped by `company_id`
4. **Inline styles for PDF** — Tailwind unreliable in DomPDF blade views; use `style=""` attributes
5. **Malaysian context** — currency MYR, dates DD/MM/YYYY, tax rates per LHDN

---

## 12. SKILL FILES

See `.claude/skills/` for detailed context on specific modules:

| File | Covers |
|---|---|
| `accounting.md` | COA structure, journal engine, period rules |
| `ar-ap.md` | Invoice/Bill flow, settlement logic |
| `payroll.md` | KWSP/SOCSO/EIS/PCB rates, payroll_lines schema |
| `multi-currency.md` | FX columns, Frankfurter API integration |
| `compliance.md` | SST, MyInvois, Billplz scaffold status |
| `reports.md` | P&L, Balance Sheet, DomPDF patterns |

---

*SAGA SME — Built for Malaysian businesses, by Sabahans.* 🇲🇾
