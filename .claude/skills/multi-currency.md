# Skill: Multi-Currency

> Context for AI assistants working on SAGA SME's multi-currency module.

---

## 1. CONFIGURATION

| Item | Value |
|---|---|
| Base currency | MYR (Malaysian Ringgit) |
| Active foreign currencies | USD, SGD |
| Rate source | Frankfurter API (free, no API key required) |
| Rate fetch | On-demand when creating FX transactions |

---

## 2. FRANKFURTER API

```
Base URL: https://api.frankfurter.app

Get latest rates (MYR base):
GET https://api.frankfurter.app/latest?base=MYR&symbols=USD,SGD

Get historical rate:
GET https://api.frankfurter.app/2026-01-15?base=MYR&symbols=USD

Response:
{
  "amount": 1.0,
  "base": "MYR",
  "date": "2026-01-15",
  "rates": {
    "USD": 0.2254,
    "SGD": 0.3012
  }
}
```

### Laravel Service
```php
// app/Services/FxRateService.php
class FxRateService
{
    public function getRate(string $currency, ?string $date = null): float
    {
        $date = $date ?? now()->format('Y-m-d');
        $url  = "https://api.frankfurter.app/{$date}?base=MYR&symbols={$currency}";
        
        $response = Http::get($url);
        
        if ($response->failed()) {
            throw new \Exception("FX rate fetch failed for {$currency}");
        }
        
        return $response->json("rates.{$currency}");
    }
    
    public function toBase(float $foreignAmount, float $rate): float
    {
        // foreign → MYR
        return round($foreignAmount / $rate, 2);
    }
    
    public function toForeign(float $baseAmount, float $rate): float
    {
        // MYR → foreign
        return round($baseAmount * $rate, 2);
    }
}
```

---

## 3. DATABASE SCHEMA — FX COLUMNS

All FX-capable tables use dual-column pattern:

```sql
-- Invoice example
currency_code   VARCHAR(3)      -- e.g. 'USD'
exchange_rate   DECIMAL(10,6)   -- e.g. 4.4350 (1 USD = 4.4350 MYR)

-- Line amounts (foreign currency)
subtotal        DECIMAL(15,2)   -- in foreign currency
total           DECIMAL(15,2)   -- in foreign currency

-- Base amounts (MYR for reporting)
base_subtotal   DECIMAL(15,2)   -- subtotal × exchange_rate
base_total      DECIMAL(15,2)   -- total × exchange_rate
```

### Journal Lines — FX Columns
```sql
journal_lines:
  debit           DECIMAL(15,2)   -- in transaction currency
  credit          DECIMAL(15,2)   -- in transaction currency
  currency_code   VARCHAR(3)
  exchange_rate   DECIMAL(10,6)
  base_debit      DECIMAL(15,2)   -- MYR equivalent
  base_credit     DECIMAL(15,2)   -- MYR equivalent
```

---

## 4. REPORTING RULE

> **All financial reports (P&L, Balance Sheet, Trial Balance) use BASE amounts (MYR).**

```php
// Always use base_debit / base_credit for reports
$totalDebit = JournalLine::sum('base_debit');

// Never use debit/credit directly for MYR reports
// (those columns may contain USD or SGD values)
```

---

## 5. FX GAIN / LOSS

When a foreign currency receivable/payable is settled at a different rate than when it was recorded:

```
Example:
- Invoice raised: USD 1,000 @ 4.40 = MYR 4,400 (base)
- Payment received: USD 1,000 @ 4.45 = MYR 4,450 (base)
- FX Gain: MYR 50

Journal for FX Gain:
DR  Cash at Bank (1100)              MYR 4,450
    CR  Accounts Receivable (1300)   MYR 4,400
    CR  FX Gain (7100)               MYR 50
```

### COA Accounts for FX
| Code | Name |
|---|---|
| 7100 | Foreign Exchange Gain |
| 8100 | Foreign Exchange Loss |

---

## 6. CURRENCIES TABLE

```
currencies
  id, code, name, symbol, is_active, is_base,
  created_at, updated_at
```

Seeded:
| Code | Name | Symbol | Active | Base |
|---|---|---|---|---|
| MYR | Malaysian Ringgit | RM | ✅ | ✅ |
| USD | US Dollar | $ | ✅ | ❌ |
| SGD | Singapore Dollar | S$ | ✅ | ❌ |

---

## 7. GOTCHAS

1. Exchange rate stored as: **1 foreign = X MYR** (e.g. USD: 4.4350)
2. Always fetch rate on transaction DATE, not today (historical accuracy)
3. Frankfurter API sometimes returns previous business day — acceptable
4. If Frankfurter API is down, allow manual rate entry as fallback
5. Rounding: use `round($amount, 2)` consistently — never rely on float math directly
