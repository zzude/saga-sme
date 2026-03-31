# SAGA SME — CLAUDE.md

## Project Overview

**SAGA SME** is a Malaysian accounting SaaS application built for small and medium enterprises (SMEs). It handles core accounting workflows including invoicing, purchases, expenses, payroll, tax compliance (SST/e-Invoice), and financial reporting — all tailored for Malaysian business requirements.

**Stack:** Laravel 12 · PHP 8.4 · Filament 5.x · MySQL 8+ · Laragon (local)

---

## Development Environment

- **Local server:** Laragon at `http://saga-sme.test`
- **Root:** `C:\laragon6\www\saga-sme`
- **PHP:** 8.4
- **Node:** 20+ (Vite)

### Common Commands

```bash
# Dependencies
composer install
npm install

# Development
npm run dev           # Vite dev server (hot reload)
php artisan serve     # only if not using Laragon

# Build
npm run build

# Database
php artisan migrate
php artisan migrate:fresh --seed
php artisan db:seed

# Filament
php artisan make:filament-resource ModelName --generate
php artisan make:filament-page PageName
php artisan make:filament-widget WidgetName

# Code generation
php artisan make:model ModelName -mfs   # model + migration + factory + seeder
php artisan make:policy PolicyName --model=ModelName

# Cache
php artisan optimize:clear
php artisan config:clear && php artisan route:clear && php artisan view:clear

# Testing
php artisan test
php artisan test --filter TestClassName
```

---

## Architecture

### Multi-Tenancy

- Each **Company** is a tenant. All data is scoped by `company_id`.
- Use `HasCompanyScope` or a global scope trait on all tenant models — always verify a query carries the company scope before executing.
- Super-admin panel operates outside tenant scope.

### Filament Panels

| Panel | Path | Purpose |
|-------|------|---------|
| `AdminPanelProvider` | `/admin` | Super-admin: tenant management, system config |
| `AppPanelProvider` | `/app` | Tenant users: accounting, invoicing, reports |

Panel providers live in `app/Providers/Filament/`.

**AdminPanelProvider uses manual resource registration** — do not rely on auto-discovery. Every resource must be explicitly listed in the `->resources([])` call inside the panel provider.

### Key Directories

```
app/
  Filament/
    Admin/          # Super-admin panel resources/pages/widgets
    App/            # Tenant panel resources/pages/widgets
  Models/           # Eloquent models
  Policies/         # Authorization policies (one per model)
  Services/         # Business logic (never in controllers or resources)
  Actions/          # Single-responsibility action classes
  Enums/            # PHP 8.1+ backed enums for statuses, types
  Traits/           # Reusable model/class traits
  Http/
    Controllers/    # Thin controllers — delegate to Services/Actions
    Requests/       # Form requests for validation
config/
database/
  migrations/
  seeders/
  factories/
resources/
  views/
    reports/        # Report Blade views — use inline style="" only (see PDF section)
  css/
  js/
routes/
  web.php
  api.php
tests/
  Feature/
  Unit/
```

---

## Domain Modules

Core accounting modules expected in this project:

- **Chart of Accounts** — 3-level hierarchy, account code range 1000–5000 (see COA section)
- **Contacts** — customers and suppliers
- **Invoicing** — sales invoices, credit notes, receipts
- **Purchases** — bills, debit notes, payments
- **Banking** — accounts, transactions, reconciliation
- **Expenses** — expense claims, categories
- **Payroll** — employee management, EPF/SOCSO/PCB (Malaysian payroll compliance)
- **Tax** — SST (Sales & Service Tax), e-Invoice (MyInvois/LHDN)
- **Reports** — P&L, Balance Sheet, Trial Balance, Cash Flow, GST/SST returns
- **Settings** — company profile, financial year, currencies, tax rates

### Development Phase Rule

**Always verify each module/phase is complete and correct before proceeding to the next.** Do not move on if migrations, seeders, models, resources, or tests for the current phase have unresolved issues.

---

## Chart of Accounts

- **Account code range:** 1000–5000
- **Hierarchy:** 3 levels — Category → Group → Account
  - Level 1 (Category): broad type, e.g. `1000 Assets`
  - Level 2 (Group): sub-grouping, e.g. `1100 Current Assets`
  - Level 3 (Account): leaf account used in transactions, e.g. `1110 Cash at Bank`
- Account codes are unique per company.
- Only Level 3 accounts are posted to — never post directly to a Category or Group.
- Account types: `asset`, `liability`, `equity`, `revenue`, `expense`

---

## Malaysian Compliance Notes

- **e-Invoice:** Integration with LHDN MyInvois API (mandatory for applicable businesses). Keep e-invoice logic in a dedicated `App\Services\EInvoice` namespace.
- **SST:** 6% service tax, 10% sales tax — configurable per item/service.
- **PCB (Potongan Cukai Bulanan):** Monthly tax deduction via Jadual PCB or LHDN e-PCB. Keep tax tables in seeders, update annually.
- **EPF/SOCSO/EIS:** Rates are tier-based and seeded from official tables. Never hard-code rates — store in `contribution_rates` table.
- **Currency:** Default `MYR`. Multi-currency support must store `exchange_rate` at time of transaction; never recalculate historical rates.
- **Date format:** Use `d/m/Y` for display, ISO 8601 (`Y-m-d`) for storage.
- **Financial year:** Configurable per company (not always Jan–Dec).

---

## Coding Conventions

### General

- Follow PSR-12. Run `./vendor/bin/pint` before committing.
- Use PHP 8.4 features: enums, readonly properties, named arguments, match expressions, property hooks.
- No logic in Blade views or Filament resource classes — delegate to `Services` or `Actions`.
- All money values stored as **integers in sen** (e.g. RM 10.50 → `1050`). Use `brick/money` or a `Money` value object for arithmetic. **Never use floats for currency.**

### Money Handling

```php
// CORRECT — store and work in sen (integer)
$amount = 1050;          // represents RM 10.50
$display = number_format($amount / 100, 2);  // "10.50"

// WRONG
$amount = 10.50;         // never store as float
```

### Models

- Every tenant model must have `company_id` and use the company global scope.
- Use Eloquent `$casts` for enums, dates, and JSON columns.
- Define `$fillable` explicitly — avoid `$guarded = []`.
- Relationships named in camelCase; foreign keys in snake_case.

### Filament 5.x Resource Conventions

These conventions are **mandatory** — they differ from Filament 3/4 patterns.

#### Schema, not Form

```php
// CORRECT — Filament 5.x
use Filament\Schemas\Schema;

public function form(Schema $schema): Schema
{
    return $schema->components([
        // ...
    ]);
}

// WRONG — old Filament 3/4 pattern, do not use
use Filament\Forms\Form;
public function form(Form $form): Form
{
    return $form->schema([...]);
}
```

#### Component Imports

```php
// Layout components — import from Filament\Schemas\Components\
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;

// Reactive form utilities — import from Filament\Schemas\Components\Utilities\
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

// Form fields — still from Filament\Forms\Components\
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
```

#### Enum Values in Form Closures (`$get()`)

`$get('field')` returns the **raw backing value** (e.g. `int`) when a user picks a new value in the form, but returns the **enum instance** when Filament fills the form from an existing model record (because the model casts it). Never cast with `(int)` directly — it throws `TypeError` on an enum.

```php
// WRONG — fails when editing an existing record
$levelValue = (int) $get('level');

// CORRECT — normalise with a private static helper
private static function levelValue(mixed $level): int
{
    return $level instanceof AccountLevel ? $level->value : (int) $level;
}

// Use in closures:
->visible(fn (Get $get) => static::levelValue($get('level')) > AccountLevel::Category->value)
->options(function (Get $get) {
    $levelValue = static::levelValue($get('level'));
    // ...
})
```

Apply this pattern for any int- or string-backed enum field read via `$get()`.

#### Actions Import

```php
// CORRECT — top-level Actions namespace
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\CreateAction;

// WRONG — do not import from Tables sub-namespace for page/resource actions
use Filament\Tables\Actions\EditAction;   // only valid inside table()->actions([])
```

#### Navigation — Methods, Not Static Properties

```php
// CORRECT — Filament 5.x uses methods
public static function getNavigationGroup(): ?string
{
    return 'Accounting';
}

public static function getNavigationLabel(): string
{
    return 'Chart of Accounts';
}

public static function getNavigationIcon(): string
{
    return 'heroicon-o-book-open';
}

// WRONG — static properties are not used in Filament 5
protected static ?string $navigationGroup = 'Accounting';
protected static ?string $navigationLabel = 'Chart of Accounts';
```

### Enums

```php
// app/Enums/InvoiceStatus.php
enum InvoiceStatus: string
{
    case Draft    = 'draft';
    case Sent     = 'sent';
    case Paid     = 'paid';
    case Overdue  = 'overdue';
    case Void     = 'void';
}
```

Cast enums in models. Use enum labels for display; never compare against raw strings.

### Services

- One service class per domain concept, e.g. `InvoiceService`, `PayrollService`.
- Services are injected via constructor; do not instantiate with `new` in controllers.
- Services must not interact with the HTTP layer (no `request()`, no redirects).

### Database

- Every migration must have a `down()` method.
- Add foreign key constraints. Use `constrained()->cascadeOnDelete()` or `restrictOnDelete()` deliberately.
- Index columns used in `WHERE` and `ORDER BY` clauses.
- Use `unsignedBigInteger` for all foreign keys (auto from `foreignId()`).
- Seeders: production-safe reference data (tax rates, account types) in dedicated seeders; test/demo data in `DatabaseSeeder` only.

### Testing

- Feature tests for all HTTP endpoints and Filament actions.
- Unit tests for `Services`, `Actions`, and calculation logic.
- Use `RefreshDatabase` trait; never share state between tests.
- Factory states for common scenarios (e.g., `Invoice::factory()->paid()->create()`).

---

## PDF / Report Views

Report Blade views (used for PDF generation via DomPDF or similar) live in `resources/views/reports/`.

**Always use inline `style=""` attributes — never Tailwind classes — in report views.**

Reason: PDF renderers do not process Tailwind's utility classes. Inline styles are the only reliable way to control layout, font sizes, spacing, colours, and table formatting in generated PDFs.

```blade
{{-- CORRECT --}}
<table style="width: 100%; border-collapse: collapse; font-size: 12px;">
    <thead>
        <tr style="background-color: #f3f4f6;">
            <th style="padding: 8px; text-align: left; border-bottom: 1px solid #e5e7eb;">
                Description
            </th>
        </tr>
    </thead>
</table>

{{-- WRONG --}}
<table class="w-full text-sm border-collapse">
    <thead>
        <tr class="bg-gray-100">
            <th class="p-2 text-left border-b border-gray-200">Description</th>
        </tr>
    </thead>
</table>
```

---

## Authorization

- Use Laravel Policies — one policy per model, registered in `AuthServiceProvider`.
- Filament panels use `canAccess()` on panel providers for top-level access control.
- Use `Gate::before()` to grant `super_admin` all permissions.
- **Roles (Spatie Permission):** `super_admin`, `admin`, `approver`, `treasurer`, `viewer`
  - `super_admin` — full system access, cross-tenant
  - `admin` — full access within their company
  - `approver` — can approve/reject transactions (invoices, expenses, payroll)
  - `treasurer` — can view and manage banking/payments
  - `viewer` — read-only access to reports and records

---

## Environment Variables (key ones)

```dotenv
APP_NAME="SAGA SME"
APP_ENV=local
APP_URL=http://saga-sme.test

DB_CONNECTION=mysql
DB_DATABASE=saga_sme

# e-Invoice / MyInvois (LHDN)
MYINVOIS_BASE_URL=
MYINVOIS_CLIENT_ID=
MYINVOIS_CLIENT_SECRET=
MYINVOIS_ENVIRONMENT=sandbox   # sandbox | production

# Mail
MAIL_MAILER=smtp

# Localisation
DEFAULT_CURRENCY=MYR
DEFAULT_TIMEZONE=Asia/Kuala_Lumpur
DEFAULT_LOCALE=en_MY
```

---

## What to Avoid

- Do not use `Form $form` / `->schema([])` — use `Schema $schema` / `->components([])` (Filament 5).
- Do not use static navigation properties (`$navigationGroup`, `$navigationLabel`) — use methods.
- Do not import actions from `Filament\Tables\Actions\` for page-level actions — use `Filament\Actions\`.
- Do not import `Section` or `Grid` from `Filament\Forms\Components\` — use `Filament\Schemas\Components\`.
- Do not import `Get` or `Set` from `Filament\Forms\` — use `Filament\Schemas\Components\Utilities\Get` and `Set`.
- Do not put business logic in Filament resource `form()` / `table()` closures.
- Do not store money as floats — always integers in sen.
- Do not use Tailwind classes in report/PDF Blade views — use inline `style=""` only.
- Do not rely on Filament auto-discovery for AdminPanel resources — register manually.
- Do not use `dd()` / `dump()` — use `Log::debug()` during development; remove before committing.
- Do not bypass company scope with `withoutGlobalScopes()` unless in a super-admin context with explicit justification.
- Do not use raw SQL unless Eloquent cannot express the query; document why.
- Do not hard-code Malaysian tax/contribution rates — they change annually.
- Do not proceed to the next module/phase until the current one is verified complete.
