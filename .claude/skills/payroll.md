# Skill: Payroll

> Context for AI assistants working on SAGA SME's payroll module.

---

## 1. TABLES

```
employees
  id, company_id, employee_no, name, ic_no, email, phone,
  department, designation, date_joined, date_resigned,
  basic_salary, bank_name, bank_account,
  epf_no, socso_no, income_tax_no,
  is_active, created_at, updated_at

payroll_periods
  id, company_id, name, month, year,
  start_date, end_date,
  status (draft/processing/posted/closed),
  posted_at, created_at, updated_at

payroll_runs
  id, company_id, period_id, employee_id,
  basic_salary, gross_salary, net_salary,
  total_deductions, total_allowances,
  epf_employee, epf_employer,
  socso_employee, socso_employer,
  eis_employee, eis_employer,
  pcb_amount, journal_id,
  status (draft/posted), created_at, updated_at

payroll_lines
  id, payroll_run_id, type (earning/deduction/statutory),
  description, amount, created_at, updated_at
```

> ⚠️ Table is `payroll_lines` — NOT `payroll_items`

---

## 2. MALAYSIAN STATUTORY RATES

### EPF (KWSP)
| Category | Employee | Employer |
|---|---|---|
| Age < 60, Malaysian | 11% | 13% (salary ≤ RM5,000) / 12% (salary > RM5,000) |
| Age ≥ 60, Malaysian | 5.5% | 6% |
| Non-Malaysian | 0% (optional) | 5% |

- Monthly wage ceiling for employer contribution: No cap (full salary)
- EPF contribution table available at KWSP website

### SOCSO (PERKESO)
| Category | Employee | Employer |
|---|---|---|
| Age < 60 | 0.5% | 1.75% |
| Age ≥ 60 | 0% | 1.25% (Employment Injury Scheme only) |

- SOCSO wage ceiling: **RM5,000/month** (contributions capped at RM5,000 wage)
- Two schemes: Employment Injury Scheme + Invalidity Scheme (age < 60 only)

### EIS (SIP — Sistem Insurans Pekerjaan)
| Rate | Employee | Employer |
|---|---|---|
| Both | 0.2% | 0.2% |

- EIS wage ceiling: **RM5,000/month**
- Not applicable for: age ≥ 57 (if contributing before 57, stop at 57), domestic workers, self-employed, civil servants

### PCB / MTD (Potongan Cukai Bulanan)
- Calculate using LHDN PCB calculator / PCB tables
- Depends on: gross salary, EPF deduction, number of dependents, spouse status
- System should use `pcb_amount` field — calculated externally or via PCB table lookup
- Simplified formula: `PCB = (Annual Chargeable Income × Tax Rate) / 12`
- Chargeable income = Gross Salary × 12 − EPF (employee) − Personal Relief (RM9,000)

---

## 3. PAYROLL JOURNAL ENTRIES

### When payroll is posted:
```
DR  Salaries Expense (6100)           [gross_salary total]
DR  EPF Expense - Employer (6110)     [epf_employer total]
DR  SOCSO Expense - Employer (6120)   [socso_employer total]
DR  EIS Expense - Employer (6130)     [eis_employer total]
    CR  Salaries Payable (2200)       [net_salary total]
    CR  EPF Payable (2210)            [epf_employee + epf_employer]
    CR  SOCSO Payable (2220)          [socso_employee + socso_employer]
    CR  EIS Payable (2230)            [eis_employee + eis_employer]
    CR  PCB Payable (2240)            [pcb_amount total]
```

### When salaries are paid:
```
DR  Salaries Payable (2200)       [net_salary]
    CR  Cash at Bank (1100)       [net_salary]
```

### When statutory payments are made (EPF/SOCSO/EIS/PCB):
```
DR  EPF Payable (2210)            [amount]
DR  SOCSO Payable (2220)          [amount]
DR  EIS Payable (2230)            [amount]
DR  PCB Payable (2240)            [amount]
    CR  Cash at Bank (1100)       [total]
```

---

## 4. PAYROLL WORKFLOW

```
1. Create Payroll Period (month/year)
2. Add employees to period → auto-calculate statutory deductions
3. Review payroll_runs → adjust allowances/deductions if needed
4. Post payroll → journal created
5. Process payment → bank transfer
6. Close period
```

---

## 5. LEAVE MANAGEMENT

### Tables
```
leave_types
  id, company_id, name, days_per_year, is_paid, created_at, updated_at
  (Annual Leave: 14 days, Medical Leave: 14 days, etc.)

leave_applications
  id, company_id, employee_id, leave_type_id,
  start_date, end_date, days, reason,
  status (pending/approved/rejected/cancelled),
  approved_by, approved_at, created_at, updated_at

leave_balances
  id, company_id, employee_id, leave_type_id, year,
  entitled, taken, balance, created_at, updated_at
```

### Malaysian Leave Entitlement (Employment Act 1955)
| Service Duration | Annual Leave |
|---|---|
| < 2 years | 8 days |
| 2–5 years | 12 days |
| > 5 years | 16 days |

- Medical leave: 14 days (< 2 years), 18 days (2–5 years), 22 days (> 5 years)
- Public holidays: 11 days mandatory

---

## 6. CASH ADVANCE

### Tables
```
cash_advances
  id, company_id, employee_id, amount, purpose, date,
  status (pending/approved/settled/cancelled),
  approved_by, approved_at, settled_at,
  journal_id, created_at, updated_at
```

### Journal: Approve Cash Advance
```
DR  Cash Advance - Employee (1400)    [amount]
    CR  Cash at Bank (1100)           [amount]
```

### Journal: Settle Cash Advance (with expense claim)
```
DR  Expense Account (6xxx)            [expense amount]
DR  Cash at Bank (1100)               [refund if any]
    CR  Cash Advance - Employee (1400) [advance amount]
```

---

## 7. DEMO DATA

- DemoDataSeeder includes 1,050 payroll deduction rows
- Test company has realistic Malaysian payroll data seeded
- Period: Jan–Mar 2026 (3 months seeded)
