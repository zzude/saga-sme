<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 9pt; color: #1a1a1a; }

    /* ── HEADER ─────────────────────────────────────────────── */
    .header { display: table; width: 100%; margin-bottom: 18px; border-bottom: 3px solid #1e3a5f; padding-bottom: 12px; }
    .header-left { display: table-cell; width: 60%; vertical-align: middle; }
    .header-right { display: table-cell; width: 40%; vertical-align: top; text-align: right; }
    .company-logo { max-height: 60px; max-width: 160px; }
    .company-name { font-size: 14pt; font-weight: bold; color: #1e3a5f; }
    .company-info { font-size: 7.5pt; color: #555; line-height: 1.5; margin-top: 3px; }

    /* ── DOC TITLE ──────────────────────────────────────────── */
    .doc-title-block { background: #1e3a5f; color: white; padding: 8px 14px; margin-bottom: 16px; display: table; width: 100%; }
    .doc-title { display: table-cell; font-size: 13pt; font-weight: bold; letter-spacing: 1px; }
    .doc-meta { display: table-cell; text-align: right; font-size: 8.5pt; vertical-align: middle; line-height: 1.7; }

    /* ── ADDRESSES ──────────────────────────────────────────── */
    .address-block { display: table; width: 100%; margin-bottom: 14px; }
    .address-to { display: table-cell; width: 55%; vertical-align: top; }
    .address-details { display: table-cell; width: 45%; vertical-align: top; }
    .label-sm { font-size: 7pt; color: #888; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px; }
    .address-to-name { font-weight: bold; font-size: 10pt; }
    .address-to-co { font-size: 9pt; margin-bottom: 3px; }
    .meta-table { width: 100%; border-collapse: collapse; font-size: 8.5pt; }
    .meta-table td { padding: 2px 4px; }
    .meta-table .lbl { color: #555; width: 45%; }
    .meta-table .val { font-weight: bold; }

    /* ── TITLE ──────────────────────────────────────────────── */
    .quotation-subject { background: #f0f4fa; padding: 7px 10px; margin-bottom: 12px; font-size: 9.5pt; }
    .quotation-subject .lbl { font-size: 7pt; color: #888; text-transform: uppercase; }
    .quotation-subject .val { font-weight: bold; }

    /* ── ITEMS TABLE ────────────────────────────────────────── */
    .items-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; font-size: 8.5pt; }
    .items-table thead tr { background: #1e3a5f; color: white; }
    .items-table thead th { padding: 6px 7px; text-align: left; }
    .items-table thead th.right { text-align: right; }
    .items-table tbody tr:nth-child(even) { background: #f7f9fc; }
    .items-table tbody td { padding: 5px 7px; border-bottom: 1px solid #e8ecf0; vertical-align: top; }
    .items-table tbody td.right { text-align: right; }
    .items-table tbody td.center { text-align: center; }
    .item-detail { font-size: 7.5pt; color: #666; margin-top: 2px; }

    /* ── TOTALS ─────────────────────────────────────────────── */
    .totals-block { display: table; width: 100%; margin-bottom: 16px; }
    .totals-spacer { display: table-cell; width: 55%; }
    .totals-table-wrap { display: table-cell; width: 45%; }
    .totals-table { width: 100%; border-collapse: collapse; font-size: 8.5pt; }
    .totals-table td { padding: 3px 7px; }
    .totals-table .lbl { color: #555; }
    .totals-table .val { text-align: right; }
    .totals-table .total-row { background: #1e3a5f; color: white; font-weight: bold; font-size: 10pt; }
    .totals-table .total-row td { padding: 6px 7px; }

    /* ── TERMS ──────────────────────────────────────────────── */
    .terms-block { margin-bottom: 14px; }
    .terms-title { font-weight: bold; font-size: 8.5pt; margin-bottom: 4px; color: #1e3a5f; }
    .terms-content { font-size: 8pt; color: #444; line-height: 1.6; white-space: pre-wrap; }

    /* ── REMARKS ────────────────────────────────────────────── */
    .remarks-block { background: #fff8e1; border-left: 3px solid #f59e0b; padding: 7px 10px; margin-bottom: 14px; font-size: 8pt; }

    /* ── SIGNATURE ──────────────────────────────────────────── */
    .signature-block { display: table; width: 100%; margin-top: 30px; }
    .sig-left { display: table-cell; width: 50%; vertical-align: top; }
    .sig-right { display: table-cell; width: 50%; vertical-align: top; text-align: right; }
    .sig-line { border-top: 1px solid #333; width: 200px; margin: 40px 0 4px 0; }
    .sig-label { font-size: 8pt; color: #555; }

    /* ── FOOTER ─────────────────────────────────────────────── */
    .footer { border-top: 1px solid #ddd; margin-top: 20px; padding-top: 6px; text-align: center; font-size: 7pt; color: #aaa; }

    /* ── REVISION BADGE ─────────────────────────────────────── */
    .revision-badge { background: #f59e0b; color: white; padding: 2px 7px; border-radius: 10px; font-size: 7.5pt; font-weight: bold; }

    .page-break { page-break-after: always; }
</style>
</head>
<body>

{{-- ── HEADER ──────────────────────────────────────────── --}}
<div class="header">
    <div class="header-left">
        @if($company->logo_path)
            <img src="{{ storage_path('app/public/' . $company->logo_path) }}" class="company-logo" alt="Logo">
        @else
            <div class="company-name">{{ $company->name }}</div>
        @endif
        <div class="company-info">
            {{ $company->address }}<br>
            Tel: {{ $company->phone }} &nbsp;|&nbsp; Email: {{ $company->email }}<br>
            @if($company->registration_no) No. Syarikat: {{ $company->registration_no }} @endif
            @if($company->sst_registration_no) &nbsp;|&nbsp; No. SST: {{ $company->sst_registration_no }} @endif
        </div>
    </div>
    <div class="header-right">
        <div style="font-size: 9pt; color: #888; text-transform: uppercase; letter-spacing: 1px;">Sebut Harga</div>
        <div style="font-size: 18pt; font-weight: bold; color: #1e3a5f;">QUOTATION</div>
    </div>
</div>

{{-- ── DOC TITLE BLOCK ─────────────────────────────────── --}}
<div class="doc-title-block">
    <div class="doc-title">
        {{ $quotation->quotation_number }}
        @if($quotation->revision > 0)
            &nbsp;<span class="revision-badge">SEMAKAN {{ $quotation->revision }}</span>
        @endif
    </div>
    <div class="doc-meta">
        Tarikh: {{ $quotation->quotation_date->format('d/m/Y') }}<br>
        Sah Hingga: {{ $quotation->valid_until->format('d/m/Y') }}
    </div>
</div>

{{-- ── ADDRESS + META ──────────────────────────────────── --}}
<div class="address-block">
    <div class="address-to">
        <div class="label-sm">Kepada</div>
        @if($quotation->attention_to)
            <div class="label-sm">U/P: {{ $quotation->attention_to }}</div>
        @endif
        <div class="address-to-name">{{ $quotation->customer_name }}</div>
        @if($quotation->customer_address)
            <div style="font-size: 8.5pt; line-height: 1.5; color: #444; margin-top: 3px;">
                {!! nl2br(e($quotation->customer_address)) !!}
            </div>
        @endif
    </div>
    <div class="address-details">
        <table class="meta-table">
            <tr><td class="lbl">No. Sebut Harga</td><td class="val">{{ $quotation->quotation_number }}</td></tr>
            <tr><td class="lbl">Tarikh</td><td class="val">{{ $quotation->quotation_date->format('d M Y') }}</td></tr>
            <tr><td class="lbl">Sah Hingga</td><td class="val">{{ $quotation->valid_until->format('d M Y') }}</td></tr>
            <tr><td class="lbl">Terma Bayaran</td><td class="val">{{ $quotation->payment_terms_days }} hari</td></tr>
            @if($quotation->revision > 0)
            <tr><td class="lbl">Versi</td><td class="val" style="color: #f59e0b;">Semakan {{ $quotation->revision }}</td></tr>
            @endif
        </table>
    </div>
</div>

{{-- ── SUBJECT ──────────────────────────────────────────── --}}
@if($quotation->title)
<div class="quotation-subject">
    <div class="lbl">Perkara / Tajuk</div>
    <div class="val">{{ $quotation->title }}</div>
</div>
@endif

{{-- ── ITEMS ────────────────────────────────────────────── --}}
<table class="items-table">
    <thead>
        <tr>
            <th style="width: 5%;">Bil.</th>
            <th style="width: 45%;">Penerangan</th>
            <th style="width: 8%;">Unit</th>
            <th class="right" style="width: 10%;">Kuantiti</th>
            <th class="right" style="width: 15%;">Harga Unit (RM)</th>
            <th class="right" style="width: 17%;">Amaun (RM)</th>
            @if($hasSst)
            <th class="right" style="width: 8%;">SST (RM)</th>
            <th class="right" style="width: 12%;">Jumlah (RM)</th>
            @endif
        </tr>
    </thead>
    <tbody>
        @foreach($quotation->items as $item)
        <tr>
            <td class="center">{{ $item->line_no }}</td>
            <td>
                {{ $item->description }}
                @if($item->detail)
                    <div class="item-detail">{{ $item->detail }}</div>
                @endif
            </td>
            <td class="center">{{ $item->unit_of_measure }}</td>
            <td class="right">{{ number_format($item->quantity, 2) }}</td>
            <td class="right">{{ number_format($item->unit_price, 2) }}</td>
            </td>
            <td class="right">{{ number_format($item->net_amount, 2) }}</td>
            @if($hasSst)
            <td class="right">
                @if($item->sst_amount > 0)
                    {{ number_format($item->sst_amount, 2) }}
                @else
                    —
                @endif
            </td>
            <td class="right">{{ number_format($item->total_amount, 2) }}</td>
            @endif
        </tr>
        @endforeach
    </tbody>
</table>

{{-- ── TOTALS ───────────────────────────────────────────── --}}
<div class="totals-block">
    <div class="totals-spacer">
        @if($quotation->remarks)
        <div class="remarks-block">
            <strong>Nota:</strong><br>
            {{ $quotation->remarks }}
        </div>
        @endif
    </div>
    <div class="totals-table-wrap">
        <table class="totals-table">
            <tr>
                <td class="lbl">Subtotal</td>
                <td class="val">RM {{ number_format($quotation->subtotal, 2) }}</td>
            </tr>
            @if($quotation->discount_amount > 0)
            <tr>
                <td class="lbl">Diskaun</td>
                <td class="val">RM {{ number_format($quotation->discount_amount, 2) }}</td>
            </tr>
            <tr>
                <td class="lbl">Amaun Selepas Diskaun</td>
                <td class="val">RM {{ number_format($quotation->taxable_amount, 2) }}</td>
            </tr>
            @endif
            @if($quotation->sst_amount > 0)
            <tr>
                <td class="lbl">SST ({{ $quotation->sst_rate }}%)</td>
                <td class="val">RM {{ number_format($quotation->sst_amount, 2) }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td>JUMLAH KESELURUHAN</td>
                <td>RM {{ number_format($quotation->total_amount, 2) }}</td>
            </tr>
        </table>
    </div>
</div>

{{-- ── TERMS ────────────────────────────────────────────── --}}
@if($quotation->terms_conditions)
<div class="terms-block">
    <div class="terms-title">Terma & Syarat</div>
    <div class="terms-content">{{ $quotation->terms_conditions }}</div>
</div>
@endif

{{-- ── SIGNATURE ────────────────────────────────────────── --}}
<div class="signature-block">
    <div class="sig-left">
        <div class="sig-line"></div>
        <div class="sig-label"><strong>{{ $company->name }}</strong><br>Disediakan oleh</div>
    </div>
    <div class="sig-right">
        <div style="border: 1px dashed #ccc; padding: 14px 20px; display: inline-block; min-width: 200px; text-align: center; margin-top: 10px;">
            <div style="font-size: 7.5pt; color: #888; margin-bottom: 30px;">Tandatangan & Cop Pelanggan</div>
            <div style="border-top: 1px solid #333; width: 150px; margin: 0 auto 4px;"></div>
            <div class="sig-label">{{ $quotation->customer_name }}<br>Tarikh: _______________</div>
        </div>
    </div>
</div>

{{-- ── FOOTER ───────────────────────────────────────────── --}}
<div class="footer">
    Sebut Harga ini dijana secara automatik oleh sistem SAGA SME &bull; {{ $quotation->quotation_number }} &bull; {{ now()->format('d/m/Y H:i') }}
</div>

</body>
</html>
