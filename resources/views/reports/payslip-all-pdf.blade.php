<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; font-size: 9pt; color: #111; }
    .page { padding: 8mm; page-break-after: always; }
    .page:last-child { page-break-after: avoid; }
    .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6mm; border-bottom: 2px solid #111; padding-bottom: 4mm; }
    .company-name { font-size: 13pt; font-weight: bold; }
    .company-sub { font-size: 8pt; color: #555; margin-top: 1mm; }
    .slip-title { text-align: right; }
    .slip-title h1 { font-size: 16pt; font-weight: bold; color: #1d4ed8; }
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
    .section-title { font-size: 8pt; font-weight: bold; color: #fff; background: #1d4ed8; padding: 2mm 3mm; text-transform: uppercase; letter-spacing: 1px; }
    .detail-table { width: 100%; border-collapse: collapse; margin-bottom: 4mm; }
    .detail-table td { padding: 1.5mm 3mm; font-size: 8.5pt; border-bottom: 1px solid #f3f4f6; }
    .detail-table tr:nth-child(even) td { background: #f9fafb; }
    .detail-table .dlabel { color: #374151; width: 60%; }
    .detail-table .dvalue { text-align: right; font-family: monospace; }
    .detail-table .dvalue.positive { color: #15803d; }
    .detail-table .dvalue.negative { color: #dc2626; }
    .net-box { margin-top: 4mm; padding: 3mm 5mm; background: #f0fdf4; border: 2px solid #16a34a; display: flex; justify-content: space-between; align-items: center; }
    .net-box .net-label { font-size: 10pt; font-weight: bold; color: #15803d; }
    .net-box .net-amount { font-size: 14pt; font-weight: bold; color: #15803d; font-family: monospace; }
    .footer { margin-top: 6mm; padding-top: 3mm; border-top: 1px solid #e5e7eb; display: flex; justify-content: space-between; font-size: 7pt; color: #9ca3af; }
    .confidential { text-align: center; font-size: 7pt; color: #9ca3af; margin-top: 2mm; }
</style>
</head>
<body>
@foreach($lines as $line)
<div class="page">
    <div class="header">
        <div>
            <div class="company-name">{{ $company->name }}</div>
            <div class="company-sub">{{ $company->registration_number ?? '' }}</div>
            <div class="company-sub">{{ $company->address ?? '' }}{{ $company->city ? ', '.$company->city : '' }}</div>
        </div>
        <div class="slip-title">
            <h1>PAYSLIP</h1>
            <div class="period">{{ $run->payrollPeriod->name ?? '-' }}</div>
            <div class="period">Ref: {{ $run->reference_no }}</div>
        </div>
    </div>

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
            </table>
        </div>
    </div>

    <div class="section-title">Pendapatan</div>
    <table class="detail-table">
        <tr><td class="dlabel">Gaji Pokok</td><td class="dvalue positive">{{ number_format($line->basic_salary, 2) }}</td></tr>
        @if($line->allowances > 0)
        <tr><td class="dlabel">Elaun</td><td class="dvalue positive">{{ number_format($line->allowances, 2) }}</td></tr>
        @endif
        <tr style="font-weight:bold; background:#f0fdf4;"><td class="dlabel">Jumlah Kasar</td><td class="dvalue positive">{{ number_format($line->gross_salary, 2) }}</td></tr>
    </table>

    <div class="section-title">Potongan</div>
    <table class="detail-table">
        @foreach($line->deductions->whereIn('component', ['kwsp_ee','socso_ee','eis_ee','pcb']) as $ded)
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
        <tr style="font-weight:bold;background:#fff7f7;"><td class="dlabel">Jumlah Potongan</td><td class="dvalue negative">( {{ number_format($line->total_employee_deduction, 2) }} )</td></tr>
    </table>

    <div class="net-box">
        <div class="net-label">Gaji Bersih (Net Salary)</div>
        <div class="net-amount">MYR {{ number_format($line->net_salary, 2) }}</div>
    </div>

    <div class="footer">
        <div>Dicetak: {{ now()->format('d/m/Y H:i') }}</div>
        <div>*** SULIT / CONFIDENTIAL ***</div>
        <div>{{ $company->name }}</div>
    </div>
</div>
@endforeach
</body>
</html>
