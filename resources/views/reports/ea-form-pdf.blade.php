<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; font-size: 9pt; color: #111; }
    .page { padding: 8mm; }
    .header { text-align: center; margin-bottom: 6mm; border-bottom: 2px solid #111; padding-bottom: 4mm; }
    .header h1 { font-size: 14pt; font-weight: bold; letter-spacing: 1px; }
    .header h2 { font-size: 11pt; margin-top: 1mm; color: #374151; }
    .header .year { font-size: 10pt; color: #1d4ed8; font-weight: bold; margin-top: 1mm; }
    .section { margin-bottom: 5mm; }
    .section-title { font-size: 8pt; font-weight: bold; background: #1e3a5f; color: #fff; padding: 2mm 3mm; margin-bottom: 0; text-transform: uppercase; letter-spacing: 1px; }
    .info-table { width: 100%; border-collapse: collapse; }
    .info-table td { padding: 1.5mm 3mm; font-size: 8.5pt; border: 1px solid #e5e7eb; }
    .info-table .ilabel { background: #f9fafb; color: #6b7280; width: 40%; font-size: 8pt; }
    .info-table .ivalue { font-weight: bold; }
    .amount-table { width: 100%; border-collapse: collapse; }
    .amount-table thead tr { background: #1e3a5f; color: #fff; }
    .amount-table thead td { padding: 2mm 3mm; font-size: 8pt; font-weight: bold; }
    .amount-table tbody td { padding: 1.5mm 3mm; font-size: 8.5pt; border-bottom: 1px solid #e5e7eb; }
    .amount-table tbody tr:nth-child(even) td { background: #f9fafb; }
    .amount-table .alabel { width: 60%; }
    .amount-table .avalue { text-align: right; font-family: monospace; }
    .amount-table .aref { color: #6b7280; font-size: 7.5pt; width: 15%; }
    .total-row td { font-weight: bold; background: #f0f9ff !important; border-top: 2px solid #111; }
    .pcb-box { margin-top: 4mm; padding: 3mm 5mm; background: #fef9c3; border: 1px solid #ca8a04; }
    .pcb-box .pcb-label { font-size: 8pt; color: #92400e; font-weight: bold; }
    .pcb-box .pcb-amount { font-size: 12pt; font-weight: bold; color: #92400e; font-family: monospace; }
    .declaration { margin-top: 6mm; padding: 3mm; border: 1px solid #e5e7eb; background: #f9fafb; font-size: 7.5pt; color: #444; line-height: 1.5; }
    .sig-section { display: flex; justify-content: space-between; margin-top: 8mm; }
    .sig-box { width: 45%; text-align: center; }
    .sig-line { border-top: 1px solid #111; margin-top: 12mm; padding-top: 1mm; font-size: 8pt; }
    .footer { margin-top: 6mm; padding-top: 3mm; border-top: 1px solid #e5e7eb; text-align: center; font-size: 7pt; color: #9ca3af; }
</style>
</head>
<body>
<div class="page">

    {{-- Header --}}
    <div class="header">
        <h1>BORANG EA</h1>
        <h2>Penyata Saraan Dari Penggajian</h2>
        <div class="year">Tahun Taksiran: {{ $year }}</div>
        <div style="font-size:8pt; color:#555; margin-top:1mm;">
            (Seksyen 83(1A) Akta Cukai Pendapatan 1967)
        </div>
    </div>

    {{-- Bahagian A: Maklumat Majikan --}}
    <div class="section">
        <div class="section-title">Bahagian A — Maklumat Majikan</div>
        <table class="info-table">
            <tr>
                <td class="ilabel">Nama Majikan</td>
                <td class="ivalue">{{ $company->name }}</td>
                <td class="ilabel">No. Pendaftaran SSM</td>
                <td class="ivalue">{{ $company->registration_number ?? '-' }}</td>
            </tr>
            <tr>
                <td class="ilabel">No. Majikan (LHDN)</td>
                <td class="ivalue">{{ $company->tax_number ?? '-' }}</td>
                <td class="ilabel">No. SST</td>
                <td class="ivalue">{{ $company->sst_number ?? 'NA' }}</td>
            </tr>
            <tr>
                <td class="ilabel">Alamat</td>
                <td class="ivalue" colspan="3">{{ $company->address ?? '-' }}{{ $company->city ? ', ' . $company->city : '' }}{{ $company->state ? ', ' . $company->state : '' }}</td>
            </tr>
        </table>
    </div>

    {{-- Bahagian B: Maklumat Pekerja --}}
    <div class="section">
        <div class="section-title">Bahagian B — Maklumat Pekerja</div>
        <table class="info-table">
            <tr>
                <td class="ilabel">Nama Pekerja</td>
                <td class="ivalue">{{ $employee->name }}</td>
                <td class="ilabel">No. Pekerja</td>
                <td class="ivalue">{{ $employee->employee_no }}</td>
            </tr>
            <tr>
                <td class="ilabel">No. Kad Pengenalan</td>
                <td class="ivalue">{{ $employee->ic_no ?? '-' }}</td>
                <td class="ilabel">No. Cukai Pendapatan</td>
                <td class="ivalue">{{ $employee->income_tax_no ?? '-' }}</td>
            </tr>
            <tr>
                <td class="ilabel">No. EPF/KWSP</td>
                <td class="ivalue">{{ $employee->epf_no ?? '-' }}</td>
                <td class="ilabel">Status Perkahwinan</td>
                <td class="ivalue">{{ ucfirst($employee->marital_status ?? '-') }} / {{ $employee->children_count ?? 0 }} anak</td>
            </tr>
            <tr>
                <td class="ilabel">Tarikh Mula Berkhidmat</td>
                <td class="ivalue">{{ $employee->date_joined?->format('d/m/Y') ?? '-' }}</td>
                <td class="ilabel">Tarikh Berhenti</td>
                <td class="ivalue">{{ $employee->date_resigned?->format('d/m/Y') ?? 'Masih Berkhidmat' }}</td>
            </tr>
        </table>
    </div>

    {{-- Bahagian C: Pendapatan --}}
    <div class="section">
        <div class="section-title">Bahagian C — Pendapatan Penggajian</div>
        <table class="amount-table">
            <thead>
                <tr>
                    <td class="alabel">Perkara</td>
                    <td class="aref">Ruangan</td>
                    <td class="avalue">Jumlah (RM)</td>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="alabel">Gaji Pokok, Upah, Komisyen, dll.</td>
                    <td class="aref">C1</td>
                    <td class="avalue">{{ number_format($summary['basic_salary'], 2) }}</td>
                </tr>
                @if($summary['allowances'] > 0)
                <tr>
                    <td class="alabel">Elaun Lain-lain</td>
                    <td class="aref">C2</td>
                    <td class="avalue">{{ number_format($summary['allowances'], 2) }}</td>
                </tr>
                @endif
                <tr class="total-row">
                    <td class="alabel">Jumlah Pendapatan Kasar</td>
                    <td class="aref">C9</td>
                    <td class="avalue">{{ number_format($summary['gross_salary'], 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Bahagian D: Caruman & Potongan --}}
    <div class="section">
        <div class="section-title">Bahagian D — Caruman Berkanun</div>
        <table class="amount-table">
            <thead>
                <tr>
                    <td class="alabel">Perkara</td>
                    <td class="aref">Ruangan</td>
                    <td class="avalue">Jumlah (RM)</td>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="alabel">Caruman KWSP/EPF (Pekerja)</td>
                    <td class="aref">D1</td>
                    <td class="avalue">{{ number_format($summary['kwsp_ee'], 2) }}</td>
                </tr>
                <tr>
                    <td class="alabel">Caruman SOCSO (Pekerja)</td>
                    <td class="aref">D2</td>
                    <td class="avalue">{{ number_format($summary['socso_ee'], 2) }}</td>
                </tr>
                <tr>
                    <td class="alabel">Caruman EIS (Pekerja)</td>
                    <td class="aref">D3</td>
                    <td class="avalue">{{ number_format($summary['eis_ee'], 2) }}</td>
                </tr>
                <tr class="total-row">
                    <td class="alabel">Jumlah Caruman Pekerja</td>
                    <td class="aref">D9</td>
                    <td class="avalue">{{ number_format($summary['total_ee_deduction'], 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- PCB Box --}}
    <div class="pcb-box">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <div>
                <div class="pcb-label">E1 — Jumlah PCB / Cukai Berjadual Ditolak</div>
                <div style="font-size:7.5pt; color:#92400e; margin-top:1mm;">Total Monthly Tax Deduction (MTD/PCB) for the year</div>
            </div>
            <div class="pcb-amount">RM {{ number_format($summary['pcb'], 2) }}</div>
        </div>
    </div>

    {{-- Declaration --}}
    <div class="declaration">
        <strong>Pengakuan Majikan:</strong> Saya mengaku bahawa maklumat yang diberikan dalam borang ini adalah benar dan betul mengikut rekod syarikat.
        Borang ini dikeluarkan mengikut Seksyen 83(1A) Akta Cukai Pendapatan 1967.
    </div>

    {{-- Signatures --}}
    <div class="sig-section">
        <div class="sig-box">
            <div class="sig-line">Tandatangan & Cop Majikan<br>Employer Signature & Stamp</div>
        </div>
        <div class="sig-box">
            <div style="font-size:8pt; color:#374151;">Tarikh / Date: _______________</div>
            <div class="sig-line">Nama & Jawatan<br>Name & Designation</div>
        </div>
    </div>

    <div class="footer">
        Dicetak: {{ now()->format('d/m/Y H:i') }} — {{ $company->name }} — SAGA SME
    </div>

</div>
</body>
</html>
