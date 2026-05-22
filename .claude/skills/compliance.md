# Skill: Compliance (SST, MyInvois, Billplz)

> Context for AI assistants working on SAGA SME's compliance modules.

---

## 1. SST (SALES AND SERVICE TAX)

### Overview
| Tax | Rate | Threshold |
|---|---|---|
| Service Tax | 8% (increased from 6%, Mar 2024) | RM500,000/year revenue |
| Sales Tax | 5% or 10% depending on goods | RM500,000/year (manufacturers) |

> Most SME service businesses: Service Tax 8% only.

### SST Tables
```
sst_settings
  id, company_id, is_registered, registration_no,
  service_tax_rate, sales_tax_rate,
  effective_date, created_at, updated_at

sst_returns
  id, company_id, period_start, period_end,
  taxable_sales, output_tax, input_tax, net_payable,
  status (draft/submitted), submitted_at, created_at, updated_at
```

### COA Accounts
| Code | Name | Notes |
|---|---|---|
| 2300 | SST Payable (Output) | Collected from customers |
| 2310 | SST Claimable (Input) | Paid to vendors (if registered) |

### Invoice with SST
```php
// When posting invoice with SST:
DR  Accounts Receivable (1300)    [subtotal + tax]
    CR  Sales Revenue (4100)      [subtotal]
    CR  SST Payable (2300)        [tax_amount]

// tax_amount = subtotal × service_tax_rate (e.g. 8%)
```

### SST Return Filing
- Filed every 2 months (bimonthly)
- Net payable = Output Tax − Input Tax
- If net negative → refund from LHDN (rare)

### SST in invoice_lines
```php
// Check if company is SST registered before applying tax
$sstSettings = SstSetting::where('company_id', $companyId)->first();
$taxRate = $sstSettings?->is_registered ? $sstSettings->service_tax_rate : 0;
$taxAmount = round($subtotal * $taxRate / 100, 2);
```

---

## 2. MYINVOIS (LHDN e-INVOICE)

### Status: Scaffold only (v1.0) — live pending LHDN credentials

### Overview
- Mandatory for all businesses (phased rollout from 2024)
- All invoices must be submitted to LHDN MyInvois portal
- LHDN validates and returns UUID + QR code
- Invoice only valid after LHDN approval

### MyInvois Tables
```
myinvois_submissions
  id, company_id, invoice_id, submission_uid,
  lhdn_uuid, lhdn_hash, qr_url,
  status (pending/submitted/valid/rejected/cancelled),
  submitted_at, validated_at, error_message,
  created_at, updated_at
```

### MyInvois Invoice Document (JSON structure)
```json
{
  "invoiceTypeCode": "01",
  "invoiceNo": "INV-2026-00001",
  "invoiceDate": "2026-01-15",
  "supplierInfo": {
    "tin": "C12345678900",
    "registrationNo": "202301234567",
    "name": "Company Name Sdn Bhd",
    "address": "..."
  },
  "buyerInfo": {
    "tin": "...",
    "name": "...",
    "address": "..."
  },
  "invoiceLines": [...],
  "taxTotal": { "taxAmount": 80.00 },
  "legalMonetaryTotal": { "payableAmount": 1080.00 }
}
```

### API Endpoints (LHDN Sandbox)
```
Base: https://preprod-api.myinvois.hasil.gov.my
POST /api/v1.0/documentsubmissions      Submit invoice
GET  /api/v1.0/documents/{uuid}/details Get submission status
POST /api/v1.0/documents/cancellation   Cancel invoice
```

### Authentication
- OAuth2 client credentials flow
- Scope: `InvoicingAPI`
- Token endpoint: `https://preprod-api.myinvois.hasil.gov.my/connect/token`

### Pending to Activate
1. Register LHDN MyInvois sandbox account
2. Get Client ID + Client Secret
3. Add to `.env`:
   ```
   MYINVOIS_CLIENT_ID=
   MYINVOIS_CLIENT_SECRET=
   MYINVOIS_BASE_URL=https://preprod-api.myinvois.hasil.gov.my
   ```
4. Implement `MyInvoisService::submit(Invoice $invoice)`

---

## 3. BILLPLZ PAYMENT GATEWAY

### Status: Scaffold only (v1.0) — live pending Billplz account

### Overview
- Malaysian payment gateway (popular for SME)
- Supports FPX (online banking), credit/debit card
- Webhook-based payment confirmation

### Billplz Tables
```
billplz_bills
  id, company_id, invoice_id, bill_id, collection_id,
  amount, paid_amount, status (pending/paid/due),
  bill_url, paid_at, created_at, updated_at
```

### API Integration
```
Base: https://www.billplz-sandbox.com/api/v3
POST /bills          Create bill
GET  /bills/{id}     Check bill status
POST /bills/{id}     Webhook notification
```

### Webhook Payload (Payment Success)
```json
{
  "id": "BILL_ID",
  "collection_id": "COLLECTION_ID",
  "paid": true,
  "state": "paid",
  "amount": 100000,       // in cents
  "paid_amount": 100000,
  "paid_at": "2026-01-15 10:30:00 +0800",
  "x_signature": "..."
}
```

### Pending to Activate
1. Register Billplz sandbox account at billplz.com
2. Get API Secret Key + Collection ID
3. Add to `.env`:
   ```
   BILLPLZ_API_KEY=
   BILLPLZ_COLLECTION_ID=
   BILLPLZ_X_SIGNATURE_KEY=
   BILLPLZ_SANDBOX=true
   ```
4. Implement `BillplzService::createBill(Invoice $invoice)`
5. Wire webhook route in `routes/api.php`

---

## 4. AUDIT TRAIL

All financial transactions use Spatie Activitylog v5:

```php
use Spatie\Activitylog\Models\Concerns\LogsActivity;

// In model
protected static function booted(): void
{
    static::created(fn ($model) => activity()
        ->performedOn($model)
        ->causedBy(auth()->user())
        ->withProperties(['attributes' => $model->toArray()])
        ->log('created'));
}
```

### Key Activitylog v5 Rules
```php
// Correct namespace
use Spatie\Activitylog\Models\Concerns\LogsActivity;

// NOT dontSubmitEmptyLogs()
->dontLogEmptyChanges()

// attribute_changes returns Collection
$activity->changes->get('attributes')  // correct
$activity->changes['attributes']       // wrong
```
