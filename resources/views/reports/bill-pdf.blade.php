<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; font-size: 9pt; color: #111; }
    .page { padding: 8mm; }
    .inv-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8mm; border-bottom: 2px solid #111; padding-bottom: 4mm; }
    .company-name { font-size: 14pt; font-weight: bold; }
    .company-sub { font-size: 8pt; color: #555; margin-top: 1mm; }
    .inv-title { text-align: right; }
    .inv-title h1 { font-size: 18pt; font-weight: bold; letter-spacing: 2px; color: #b45309; }
    .inv-title .inv-no { font-size: 10pt; font-weight: bold; margin-top: 1mm; }
    .inv-title .inv-status { display: inline-block; margin-top: 2mm; padding: 1mm 4mm; border-radius: 3px; font-size: 8pt; font-weight: bold; background: #fef3c7; color: #b45309; }
    .inv-meta { display: flex; justify-content: space-between; margin-bottom: 6mm; }
    .bill-to { width: 55%; }
    .bill-to .label { font-size: 7pt; font-weight: bold; color: #6b7280; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 1mm; }
    .bill-to .vendor-name { font-size: 10pt; font-weight: bold; }
    .bill-to .vendor-detail { font-size: 8pt; color: #444; line-height: 1.5; }
    .inv-details { width: 40%; text-align: right; }
    .inv-details table { width: 100%; font-size: 8pt; }
    .inv-details td { padding: 1mm 0; }
    .inv-details .dlabel { color: #6b7280; }
    .inv-details .dvalue { font-weight: bold; }
    .lines-table { width: 100%; border-collapse: collapse; margin-bottom: 4mm; }
    .lines-table thead tr { background: #b45309; color: #fff; }
    .lines-table thead td { padding: 2mm 3mm; font-size: 8pt; font-weight: bold; }
    .lines-table tbody tr:nth-child(even) { background: #fffbeb; }
    .lines-table tbody td { padding: 2mm 3mm; font-size: 8.5pt; border-bottom: 1px solid #e5e7eb; }
    .text-right { text-align: right; }
    .totals { float: right; width: 45%; margin-bottom: 6mm; }
    .totals table { width: 100%; font-size: 9pt; border-collapse: collapse; }
    .totals td { padding: 1.5mm 3mm; }
    .totals .tlabel { color: #374151; }
    .totals .tvalue { text-align: right; font-family: monospace; }
    .totals .subtotal-row td { border-top: 1px solid #e5e7eb; }
    .totals .total-row td { border-top: 2px solid #111; border-bottom: 2px solid #111; font-weight: bold; font-size: 10pt; background: #fffbeb; }
    .totals .balance-row td { font-weight: bold; color: #dc2626; }
    .clearfix { clear: both; }
    .notes { margin-top: 4mm; padding: 3mm; background: #f9fafb; border-left: 3px solid #b45309; font-size: 8pt; color: #444; }
    .notes .nlabel { font-weight: bold; margin-bottom: 1mm; }
    .footer { margin-top: 8mm; padding-top: 3mm; border-top: 1px solid #e5e7eb; text-align: center; font-size: 7pt; color: #9ca3af; }
</style>
</head>
<body>
<div class="page">

    <div class="inv-header">
        <div>
            <div class="company-name">{{ $company->name }}</div>
            @if($company->address ?? null)
            <div class="company-sub">{{ $company->address }}</div>
            @endif
            @if($company->registration_no ?? null)
            <div class="company-sub">Reg No: {{ $company->registration_no }}</div>
            @endif
        </div>
        <div class="inv-title">
            <h1>BILL</h1>
            <div class="inv-no">{{ $bill->bill_no }}</div>
            @if($bill->reference_no)
            <div style="font-size:8pt;color:#666;margin-top:1mm;">Ref: {{ $bill->reference_no }}</div>
            @endif
            <div class="inv-status">{{ strtoupper($bill->status) }}</div>
        </div>
    </div>

    <div class="inv-meta">
        <div class="bill-to">
            <div class="label">Vendor</div>
            <div class="vendor-name">{{ $bill->vendor->name }}</div>
            @if($bill->vendor->address)
            <div class="vendor-detail">{{ $bill->vendor->address }}</div>
            @endif
            @if($bill->vendor->email)
            <div class="vendor-detail">{{ $bill->vendor->email }}</div>
            @endif
            @if($bill->vendor->registration_no)
            <div class="vendor-detail">Reg No: {{ $bill->vendor->registration_no }}</div>
            @endif
            @if($bill->vendor->tax_id)
            <div class="vendor-detail">Tax ID: {{ $bill->vendor->tax_id }}</div>
            @endif
        </div>
        <div class="inv-details">
            <table>
                <tr><td class="dlabel">Bill Date</td><td class="dvalue">{{ $bill->date->format('d/m/Y') }}</td></tr>
                <tr><td class="dlabel">Due Date</td><td class="dvalue">{{ $bill->due_date ? $bill->due_date->format('d/m/Y') : '—' }}</td></tr>
                <tr><td class="dlabel">Currency</td><td class="dvalue">{{ $bill->currency_code ?? 'MYR' }}</td></tr>
                @if($bill->period)
                <tr><td class="dlabel">Period</td><td class="dvalue">{{ $bill->period->name }}</td></tr>
                @endif
            </table>
        </div>
    </div>

    <table class="lines-table">
        <thead>
            <tr>
                <td style="width:5%">#</td>
                <td style="width:45%">Description</td>
                <td class="text-right" style="width:12%">Qty</td>
                <td class="text-right" style="width:15%">Unit Price</td>
                <td class="text-right" style="width:10%">Tax</td>
                <td class="text-right" style="width:13%">Amount</td>
            </tr>
        </thead>
        <tbody>
            @foreach($bill->lines as $i => $line)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $line->description }}</td>
                <td class="text-right">{{ number_format($line->quantity, 2) }}</td>
                <td class="text-right">{{ number_format($line->unit_price, 2) }}</td>
                <td class="text-right">{{ $line->tax_amount > 0 ? number_format($line->tax_amount, 2) : '—' }}</td>
                <td class="text-right">{{ number_format($line->line_total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr class="subtotal-row">
                <td class="tlabel">Subtotal</td>
                <td class="tvalue">{{ number_format($bill->subtotal, 2) }}</td>
            </tr>
            <tr>
                <td class="tlabel">Tax Amount</td>
                <td class="tvalue">{{ number_format($bill->tax_amount, 2) }}</td>
            </tr>
            <tr class="total-row">
                <td class="tlabel">TOTAL (MYR)</td>
                <td class="tvalue">{{ number_format($bill->total, 2) }}</td>
            </tr>
            @if($bill->paid_amount > 0)
            <tr>
                <td class="tlabel">Paid</td>
                <td class="tvalue">{{ number_format($bill->paid_amount, 2) }}</td>
            </tr>
            <tr class="balance-row">
                <td class="tlabel">Balance Due</td>
                <td class="tvalue">{{ number_format($bill->balance_due, 2) }}</td>
            </tr>
            @endif
        </table>
    </div>
    <div class="clearfix"></div>

    @if($bill->notes)
    <div class="notes">
        <div class="nlabel">Notes</div>
        {{ $bill->notes }}
    </div>
    @endif

    <div class="footer">
        Generated by SAGA SME &nbsp;|&nbsp; {{ now()->format('d/m/Y H:i') }} &nbsp;|&nbsp; This is a computer-generated document.
    </div>

</div>
</body>
</html>