# Skill: Accounts Receivable & Accounts Payable

> Context for AI assistants working on SAGA SME's AR/AP modules.

---

## 1. ACCOUNTS RECEIVABLE (AR)

### Tables
```
customers
  id, company_id, code, name, email, phone, address,
  credit_limit, payment_terms, is_active, created_at, updated_at

invoices
  id, company_id, invoice_no, customer_id, date, due_date,
  subtotal, discount_amount, tax_amount, total,
  status (draft/sent/partial/paid/overdue/void),
  posted_at, journal_id, currency_code, exchange_rate,
  base_subtotal, base_total, created_at, updated_at

invoice_lines
  id, invoice_id, description, qty, unit_price, discount_pct,
  tax_rate, tax_amount, subtotal, total,
  account_id, currency_code, created_at, updated_at

payments_received
  id, company_id, customer_id, invoice_id, date,
  amount, payment_method, reference, journal_id, created_at, updated_at
```

### Invoice Number Format
```
INV-YYYY-NNNNN    e.g. INV-2026-00001
```

### Invoice Status Flow
```
draft → sent → partial (payment received) → paid
                    ↓
                  void (reversal journal created)
```

### Posting Invoice (AR Journal)
```
DR  Accounts Receivable (1300)   [total]
    CR  Sales Revenue (4100)     [subtotal]
    CR  SST Payable (2300)       [tax_amount, if applicable]
```

### Recording Payment Received
```
DR  Cash at Bank (1100)          [amount]
    CR  Accounts Receivable (1300) [amount]
```

### Key Rule
- `posted_at` column determines if invoice is posted (NOT `is_posted` boolean)
- Invoice cannot be edited after posting — void and re-create
- `invoice_lines.account_id` = income account (default 4100, overrideable per line)

---

## 2. ACCOUNTS PAYABLE (AP)

### Tables
```
vendors
  id, company_id, code, name, email, phone, address,
  payment_terms, bank_account, is_active, created_at, updated_at

vendor_bills
  id, company_id, bill_no, vendor_id, vendor_ref, date, due_date,
  subtotal, tax_amount, total,
  status (draft/posted/partial/paid/void),
  posted_at, journal_id, currency_code, exchange_rate,
  base_subtotal, base_total, created_at, updated_at

vendor_bill_lines
  id, vendor_bill_id, description, qty, unit_price,
  tax_rate, tax_amount, subtotal, total,
  account_id, created_at, updated_at

payments_made
  id, company_id, vendor_id, bill_id, date,
  amount, payment_method, reference, journal_id, created_at, updated_at
```

### Posting Vendor Bill (AP Journal)
```
DR  Expense Account (6xxx)        [subtotal]
DR  SST Claimable (2310)          [tax_amount, if SST registered]
    CR  Accounts Payable (2100)   [total]
```

### Recording Payment Made
```
DR  Accounts Payable (2100)       [amount]
    CR  Cash at Bank (1100)       [amount]
```

---

## 3. QUOTATION MODULE

### Table: `quotations`
```
id, company_id, quotation_no, customer_id, date, valid_until,
subtotal, discount_amount, tax_amount, total,
status (draft/sent/accepted/rejected/converted),
converted_to_invoice_id, revision_number,
currency_code, exchange_rate, notes, created_at, updated_at
```

### Quotation Number Format
```
QT-YYYY-NNNNN         e.g. QT-2026-00001
QT-YYYY-NNNNN-R1      e.g. QT-2026-00001-R1  (revision 1)
```

### Status Flow
```
draft → sent → accepted → converted (to Invoice)
          ↓
        rejected
```

### Key Commit
- Quotation module: commit `65224f6`

### Known Bugs (Pending Fix)
1. qty/unit display — not showing correctly in table view
2. ringkasan (summary) showing RM 0.00 — calculation bug
3. PDF route — not wired up yet

### Convert to Invoice Logic
```php
// When converting quotation to invoice:
// 1. Create invoice from quotation data
// 2. Set quotation status = 'converted'
// 3. Set quotation.converted_to_invoice_id = invoice.id
// 4. Do NOT auto-post invoice — leave as draft for review
```

---

## 4. COMMON PATTERNS

### Outstanding AR Balance
```php
// Amount still owed by customer
$outstanding = Invoice::where('customer_id', $customerId)
    ->whereIn('status', ['sent', 'partial', 'overdue'])
    ->whereNotNull('posted_at')
    ->sum('total')
    - PaymentReceived::where('customer_id', $customerId)->sum('amount');
```

### Aging Report (AR)
```php
// Group by days overdue
$today = now();
// 0-30 days, 31-60 days, 61-90 days, 90+ days
$aging = Invoice::whereNotNull('posted_at')
    ->whereIn('status', ['sent', 'partial', 'overdue'])
    ->get()
    ->groupBy(fn($inv) => match(true) {
        $today->diffInDays($inv->due_date) <= 30  => '0-30',
        $today->diffInDays($inv->due_date) <= 60  => '31-60',
        $today->diffInDays($inv->due_date) <= 90  => '61-90',
        default                                    => '90+',
    });
```

### Void Invoice
```php
// 1. Create reversal journal (swap DR/CR)
// 2. Set invoice status = 'void'
// 3. Link journal reversal
// Cannot void if payments already applied — reverse payments first
```
