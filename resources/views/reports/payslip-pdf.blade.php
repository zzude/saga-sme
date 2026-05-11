<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; font-size: 9pt; color: #111; }
    .page { padding: 8mm; }
    .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6mm; border-bottom: 2px solid #111; padding-bottom: 4mm; }
    .company-name { font-size: 13pt; font-weight: bold; }
    .company-sub { font-size: 8pt; color: #555; margin-top: 1mm; }
    .slip-title { text-align: right; }
    .slip-title h1 { font-size: 16pt; font-weight: bold; color: #1d4ed8; letter-spacing: 1px; }
    .slip-title .period { font-size: 9pt; margin-top: 1mm; color: #374151; }
    .meta { display: flex; justify-content: space-between; margin-bottom: 5mm; }
    .emp-box { width: 55%; background: #f8faff; border: 1px solid #e5e7eb; padding: 3mm; border-radius: 3px; }
    .emp-box .label { font-size: 7pt; color: #6b7280; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 1mm; }
    .emp-box .emp-name { font-size: 11pt; font-weight: bold; }
    .emp-box .emp-detail { font-size: 8pt; color: #444; line-height: 1.6; margin-top: 1mm; }
    .run-box { width: 40%; text-align: right; }
    .run-box table { width: 100%; font-size: 8pt; }
    .run-box td { padding: 1mm 0; }
    .run-box .rlabel { color: #6b7280; }
    .run-box .rvalue { font-weight: bold; }
    .section-title { font-size: 8pt; font-weight: bold; color: #fff; background: #1d4ed8; padding: 2mm 3mm; margin-bottom: 0; text-transform: uppercase; letter-spacing: 1px; }
    .detail-table { width: 100%; border-collapse: collapse; margin-bottom: 4mm; }
    .detail-table td { padding: 1.5mm 3mm; font-size: 8.5pt; border-bottom: 1px solid #f3f4f6; }
    .detail-table tr:nth-child(even) td { background: #f9fafb; }
    .detail-table .dlabel { color: #374151; width: 60%; }
    .detail-table .dvalue { text-align: right; font-family: monospace; }
    .detail-table .dvalue.positive { color: #15803d; }
    .detail-table .dvalue.negative { color: #dc2626; }
    .summary { display: flex; justify-content: space-between; margin-top: 2mm; }
    .summary-box { width: 48%; border: 1px solid #e5e7eb; padding: 3mm; }
    .summary-box .stitle { font-size: 8pt; font-weight: bold; color: #6b7280; text-transform: uppercase; margin-bottom: 2mm; }
    .summary-box table { width: 100%; font-size: 8.5pt; }
    .summary-box td { padding: 1mm 0; }
    .summary-box .slabel { color: #374151; }
    .summary-box .svalue { text-align: right; font-family: monospace; }
    .net-box { margin-top: 4mm; padding: 3mm 5mm; background: #f0fdf4; border: 2px solid #16a34a; display: flex; justify-content: space-between; align-items: center; }
    .net-box .net-label { font-size: 10pt; font-weight: bold; color: #15803d; }
    .net-box .net-amount { font-size: 14pt; font-weight: bold; color: #15803d; font-family: monospace; }
    .bank-box { margin-top: 3mm; padding: 2mm 3mm; background: #f8faff; border: 1px solid #dbeafe; font-size: 8pt; }
    .bank-box .blabel { color: #6b7280; font-size: 7pt; text-transform: uppercase; }
    .footer { margin-top: 8mm; padding-top: 3mm; border-top: 1px solid #e5e7eb; display: flex; justify-content: space-between; font-size: 7pt; color: #9ca3af; }
    .sig-box { text-align: center; width: 35%; }
    .sig-line { border-top: 1px solid #111; margin-top: 10mm; padding-top: 1mm; font-size: 7.5pt; color: #374151; }
    .confidential { text-align: center; font-size: 7pt; color: #9ca3af; margin-top: 3mm; }
</style>
</head>
<body>
<div class="page">

    {{-- Header --}}
    <div class="header">
        <div>
            <div class="company-name">{{ $company->name }}</div>
            <div class="company-sub">{{ $company->registration_number ?? '' }}</div>
            <div class="company-sub">{{ $company->address ?? '' }}{{ $company->city ? ', ' . $company->city : '' }}</div>
        </div>
        <div class="slip-title">
            <h1>PAYSLIP</h1>
            <div class="period">{{ $run->payrollPeriod->name ?? '-' }}</div>
            <div class="period">Ref: {{ $run->reference_no }}</div>
        </div>
    </div>

    {{-- Employee Info + Run Details --}}
    <div class="meta">
        <div class="emp-box">
            <div class="label">Employee</div>
            <div class="emp-name">{{ $line->employee->name }}</div>
            <div class="emp-detail">
                No: {{ $line->employee->employee_no }}<br>
                IC: {{ $line->employee->ic_no ?? '-' }}<br>
                Jawatan: {{ $line->employee->position ?? '-' }}<br>
                Jabatan: {{ $line->employee->department ?? '-' }}
            </div>
        </div>
        <div class="run-box">
            <table>
                <tr><td class="rlabel">No. EPF</td><td class="rvalue">{{ $line->employee->epf_no ?? '-' }}</td></tr>
                <tr><td class="rlabel">No. SOCSO</td><td class="rvalue">{{ $line->employee->socso_no ?? '-' }}</td></tr>
                <tr><td class="rlabel">No. Cukai</td><td class="rvalue">{{ $line->employee->income_tax_no ?? '-' }}</td></tr>
                <tr><td class="rlabel">Bank</td><td class="rvalue">{{ $line->employee->bank_name ?? '-' }}</td></tr>
                <tr><td class="rlabel">No. Akaun</td><td class="rvalue">{{ $line->employee->bank_account_no ?? '-' }}</td></tr>
            </table>
        </div>
    </div>

    {{-- Earnings --}}
    <div class="section-title">Pendapatan (Earnings)</div>
    <table class="detail-table">
        <tr><td class="dlabel">Gaji Pokok (Basic Salary)</td><td class="dvalue positive">{{ number_format($line->basic_salary, 2) }}</td></tr>
        @if($line->allowances > 0)
        <tr><td class="dlabel">Elaun (Allowances)</td><td class="dvalue positive">{{ number_format($line->allowances, 2) }}</td></tr>
        @endif
        <tr style="font-weight:bold; background:#f0fdf4;"><td class="dlabel">Jumlah Kasar (Gross Salary)</td><td class="dvalue positive">{{ number_format($line->gross_salary, 2) }}</td></tr>
    </table>

    {{-- Deductions --}}
    <div class="section-title">Potongan (Deductions)</div>
    <table class="detail-table">
        @foreach($deductions as $ded)
        <tr>
            <td class="dlabel">{{ match($ded->component) {
                'kwsp_ee'  => 'KWSP/EPF (Pekerja)',
                'socso_ee' => 'SOCSO (Pekerja)',
                'eis_ee'   => 'EIS (Pekerja)',
                'pcb'      => 'PCB / Cukai Pendapatan',
                default    => $ded->component,
            } }}</td>
            <td class="dvalue negative">( {{ number_format($ded->amount, 2) }} )</td>
        </tr>
        @endforeach
        <tr style="font-weight:bold; background:#fff7f7;"><td class="dlabel">Jumlah Potongan</td><td class="dvalue negative">( {{ number_format($line->total_employee_deduction, 2) }} )</td></tr>
    </table>

    {{-- Net + Bank --}}
    <div class="net-box">
        <div class="net-label">Gaji Bersih (Net Salary)</div>
        <div class="net-amount">MYR {{ number_format($line->net_salary, 2) }}</div>
    </div>

    @if($line->employee->bank_name)
    <div class="bank-box">
        <span class="blabel">Bayaran ke: </span>
        <strong>{{ $line->employee->bank_name }}</strong> — {{ $line->employee->bank_account_no ?? '-' }}
    </div>
    @endif

    {{-- Employer Contributions --}}
    <div style="margin-top: 4mm;">
    <div class="section-title">Caruman Majikan (Employer Contributions)</div>
    <table class="detail-table">
        @foreach($erDeductions as $ded)
        <tr>
            <td class="dlabel">{{ match($ded->component) {
                'kwsp_er'  => 'KWSP/EPF (Majikan)',
                'socso_er' => 'SOCSO (Majikan)',
                'eis_er'   => 'EIS (Majikan)',
                default    => $ded->component,
            } }}</td>
            <td class="dvalue">{{ number_format($ded->amount, 2) }}</td>
        </tr>
        @endforeach
        <tr style="font-weight:bold;"><td class="dlabel">Jumlah Kos Majikan</td><td class="dvalue">{{ number_format($line->total_employer_cost, 2) }}</td></tr>
    </table>
    </div>

    {{-- Footer --}}
    <div class="footer">
        <div>Dicetak: {{ now()->format('d/m/Y H:i') }}</div>
        <div class="sig-box">
            <div class="sig-line">Tandatangan Majikan / Employer Signature</div>
        </div>
        <div>{{ $company->name }}</div>
    </div>
    <div class="confidential">*** SULIT — UNTUK KEGUNAAN PERIBADI SAHAJA / CONFIDENTIAL ***</div>

</div>
</body>
</html>
