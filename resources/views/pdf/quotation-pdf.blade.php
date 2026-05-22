<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family:'DejaVu Sans',sans-serif;
    font-size:9pt;
    color:#1f2937;
    padding:18px;
    line-height:1.5;
}

/* ─────────────────────────────────────
   HEADER
───────────────────────────────────── */

.header{
    display:table;
    width:100%;
    margin-bottom:24px;
    border-bottom:2px solid #1e3a5f;
    padding-bottom:16px;
}

.header-left{
    display:table-cell;
    width:60%;
    vertical-align:middle;
}

.header-right{
    display:table-cell;
    width:40%;
    text-align:right;
    vertical-align:top;
}

.company-logo{
    max-height:65px;
    max-width:180px;
}

.company-name{
    font-size:18pt;
    font-weight:700;
    color:#1e3a5f;
    letter-spacing:.3px;
}

.company-info{
    font-size:8pt;
    color:#666;
    line-height:1.7;
    margin-top:5px;
}

.doc-small-title{
    font-size:8pt;
    color:#888;
    text-transform:uppercase;
    letter-spacing:1px;
    margin-bottom:2px;
}

.doc-main-title{
    font-size:26pt;
    font-weight:800;
    color:#1e3a5f;
    letter-spacing:1px;
}

/* ─────────────────────────────────────
   DOCUMENT TITLE
───────────────────────────────────── */

.doc-title-block{
    background:#1e3a5f;
    color:white;
    padding:12px 16px;
    border-radius:4px;
    margin-bottom:18px;
    display:table;
    width:100%;
}

.doc-title{
    display:table-cell;
    font-size:14pt;
    font-weight:700;
    letter-spacing:1px;
    vertical-align:middle;
}

.doc-meta{
    display:table-cell;
    text-align:right;
    vertical-align:middle;
    font-size:8.5pt;
    line-height:1.8;
}

.revision-badge{
    background:#f59e0b;
    color:white;
    padding:3px 8px;
    border-radius:12px;
    font-size:7pt;
    font-weight:bold;
}

/* ─────────────────────────────────────
   ADDRESS BLOCK
───────────────────────────────────── */

.address-block{
    display:table;
    width:100%;
    margin-bottom:16px;
}

.address-to{
    display:table-cell;
    width:55%;
    vertical-align:top;
}

.address-details{
    display:table-cell;
    width:45%;
    vertical-align:top;
}

.label-sm{
    font-size:7pt;
    color:#9ca3af;
    text-transform:uppercase;
    letter-spacing:.5px;
    margin-bottom:2px;
}

.address-to-name{
    font-size:11pt;
    font-weight:700;
    color:#111827;
    margin-top:2px;
}

.customer-address{
    font-size:8.5pt;
    line-height:1.6;
    color:#4b5563;
    margin-top:4px;
}

.meta-table{
    width:100%;
    border-collapse:collapse;
    font-size:8.5pt;
}

.meta-table td{
    padding:3px 4px;
}

.meta-table .lbl{
    color:#6b7280;
    width:45%;
}

.meta-table .val{
    font-weight:700;
    color:#111827;
}

/* ─────────────────────────────────────
   SUBJECT
───────────────────────────────────── */

.quotation-subject{
    background:#f3f6fb;
    border-left:4px solid #1e3a5f;
    padding:10px 12px;
    margin-bottom:16px;
    border-radius:3px;
}

.quotation-subject .lbl{
    font-size:7pt;
    color:#9ca3af;
    text-transform:uppercase;
    margin-bottom:3px;
}

.quotation-subject .val{
    font-size:10pt;
    font-weight:700;
    color:#111827;
}

/* ─────────────────────────────────────
   ITEMS TABLE
───────────────────────────────────── */

.items-table{
    width:100%;
    border-collapse:collapse;
    margin-bottom:18px;
    font-size:8.5pt;
}

.items-table thead tr{
    background:#1e3a5f;
    color:white;
}

.items-table thead th{
    padding:10px 8px;
    text-align:left;
    font-weight:600;
    font-size:8pt;
}

.items-table thead th.right{
    text-align:right;
}

.items-table tbody tr:nth-child(even){
    background:#fafbfd;
}

.items-table tbody td{
    padding:9px 8px;
    border-bottom:.5px solid #e5e7eb;
    vertical-align:top;
}

.items-table tbody td.right{
    text-align:right;
}

.items-table tbody td.center{
    text-align:center;
}

.item-detail{
    font-size:7.5pt;
    color:#6b7280;
    margin-top:3px;
}

/* ─────────────────────────────────────
   TOTAL SECTION
───────────────────────────────────── */

.totals-block{
    display:table;
    width:100%;
    margin-bottom:20px;
}

.totals-spacer{
    display:table-cell;
    width:55%;
    vertical-align:top;
}

.totals-table-wrap{
    display:table-cell;
    width:45%;
}

.totals-table{
    width:100%;
    border-collapse:collapse;
    font-size:8.5pt;
}

.totals-table td{
    padding:5px 8px;
}

.totals-table .lbl{
    color:#4b5563;
}

.totals-table .val{
    text-align:right;
    color:#111827;
}

.total-row{
    background:#1e3a5f;
    color:white;
    font-weight:700;
    font-size:11pt;
    letter-spacing:.3px;
}

.total-row td{
    padding:11px 10px !important;
}

/* ─────────────────────────────────────
   REMARKS
───────────────────────────────────── */

.remarks-block{
    background:#fffbeb;
    border-left:4px solid #f59e0b;
    padding:10px 12px;
    border-radius:3px;
    font-size:8pt;
    color:#444;
}

/* ─────────────────────────────────────
   TERMS
───────────────────────────────────── */

.terms-block{
    margin-top:12px;
    margin-bottom:18px;
}

.terms-title{
    font-weight:700;
    font-size:8.5pt;
    color:#1e3a5f;
    margin-bottom:5px;
}

.terms-content{
    font-size:8pt;
    color:#444;
    line-height:1.7;
    white-space:pre-wrap;
}

/* ─────────────────────────────────────
   SIGNATURE
───────────────────────────────────── */

.signature-block{
    display:table;
    width:100%;
    margin-top:40px;
}

.sig-left{
    display:table-cell;
    width:50%;
    vertical-align:top;
}

.sig-right{
    display:table-cell;
    width:50%;
    vertical-align:top;
    text-align:right;
}

.sig-line{
    border-top:1px solid #444;
    width:220px;
    margin:55px 0 6px 0;
}

.sig-label{
    font-size:8pt;
    color:#555;
    line-height:1.6;
}

.customer-sign-box{
    border:1px dashed #d1d5db;
    background:#fafafa;
    border-radius:4px;
    padding:16px 20px;
    display:inline-block;
    min-width:220px;
    text-align:center;
}

.customer-sign-title{
    font-size:7.5pt;
    color:#888;
    margin-bottom:35px;
}

.customer-sign-line{
    border-top:1px solid #333;
    width:150px;
    margin:0 auto 5px;
}

/* ─────────────────────────────────────
   FOOTER
───────────────────────────────────── */

.footer{
    border-top:.5px solid #e5e7eb;
    margin-top:30px;
    padding-top:10px;
    text-align:center;
    font-size:7pt;
    color:#999;
}

.page-break{
    page-break-after:always;
}

</style>
</head>

<body>

{{-- ───────────────── HEADER ───────────────── --}}

<div class="header">

    <div class="header-left">

        @if($company->logo_path)
            <img 
                src="{{ storage_path('app/public/' . $company->logo_path) }}"
                class="company-logo"
                alt="Logo"
            >
        @else
            <div class="company-name">
                {{ $company->name }}
            </div>
        @endif

        <div class="company-info">
            {{ $company->address }}<br>

            Tel: {{ $company->phone }}
            &nbsp;|&nbsp;
            Email: {{ $company->email }}

            <br>

            @if($company->registration_no)
                No. Syarikat: {{ $company->registration_no }}
            @endif

            @if($company->sst_registration_no)
                &nbsp;|&nbsp;
                No. SST: {{ $company->sst_registration_no }}
            @endif
        </div>

    </div>

    <div class="header-right">

        <div class="doc-small-title">
            Sebut Harga
        </div>

        <div class="doc-main-title">
            QUOTATION
        </div>

    </div>

</div>

{{-- ───────────────── DOC TITLE ───────────────── --}}

<div class="doc-title-block">

    <div class="doc-title">

        {{ $quotation->quotation_number }}

        @if($quotation->revision > 0)
            &nbsp;
            <span class="revision-badge">
                SEMAKAN {{ $quotation->revision }}
            </span>
        @endif

    </div>

    <div class="doc-meta">

        Tarikh:
        {{ $quotation->quotation_date->format('d/m/Y') }}

        <br>

        Sah Hingga:
        {{ $quotation->valid_until->format('d/m/Y') }}

    </div>

</div>

{{-- ───────────────── ADDRESS ───────────────── --}}

<div class="address-block">

    <div class="address-to">

        <div class="label-sm">
            Kepada
        </div>

        @if($quotation->attention_to)
            <div class="label-sm">
                U/P: {{ $quotation->attention_to }}
            </div>
        @endif

        <div class="address-to-name">
            {{ $quotation->customer_name }}
        </div>

        @if($quotation->customer_address)
            <div class="customer-address">
                {!! nl2br(e($quotation->customer_address)) !!}
            </div>
        @endif

    </div>

    <div class="address-details">

        <table class="meta-table">

            <tr>
                <td class="lbl">No. Sebut Harga</td>
                <td class="val">{{ $quotation->quotation_number }}</td>
            </tr>

            <tr>
                <td class="lbl">Tarikh</td>
                <td class="val">
                    {{ $quotation->quotation_date->format('d M Y') }}
                </td>
            </tr>

            <tr>
                <td class="lbl">Sah Hingga</td>
                <td class="val">
                    {{ $quotation->valid_until->format('d M Y') }}
                </td>
            </tr>

            <tr>
                <td class="lbl">Terma Bayaran</td>
                <td class="val">
                    {{ $quotation->payment_terms_days }} hari
                </td>
            </tr>

        </table>

    </div>

</div>

{{-- ───────────────── SUBJECT ───────────────── --}}

@if($quotation->title)

<div class="quotation-subject">

    <div class="lbl">
        Perkara / Tajuk
    </div>

    <div class="val">
        {{ $quotation->title }}
    </div>

</div>

@endif

{{-- ───────────────── ITEMS TABLE ───────────────── --}}

<table class="items-table">

    <thead>

        <tr>

            <th style="width:5%;">
                Bil.
            </th>

            <th style="width:45%;">
                Penerangan
            </th>

            <th style="width:8%;">
                Unit
            </th>

            <th class="right" style="width:10%;">
                Kuantiti
            </th>

            <th class="right" style="width:15%;">
                Harga Unit (RM)
            </th>

            <th class="right" style="width:17%;">
                Amaun (RM)
            </th>

        </tr>

    </thead>

    <tbody>

        @foreach($quotation->items as $item)

        <tr>

            <td class="center">
                {{ $item->line_no }}
            </td>

            <td>

                {{ $item->description }}

                @if($item->detail)
                    <div class="item-detail">
                        {{ $item->detail }}
                    </div>
                @endif

            </td>

            <td class="center">
                {{ $item->unit_of_measure }}
            </td>

            <td class="right">
                {{ number_format($item->quantity, 2) }}
            </td>

            <td class="right">
                {{ number_format($item->unit_price, 2) }}
            </td>

            <td class="right">
                {{ number_format($item->net_amount, 2) }}
            </td>

        </tr>

        @endforeach

    </tbody>

</table>

{{-- ───────────────── TOTAL ───────────────── --}}

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
                <td class="val">
                    RM {{ number_format($quotation->subtotal, 2) }}
                </td>
            </tr>

            @if($quotation->discount_amount > 0)

            <tr>
                <td class="lbl">Diskaun</td>
                <td class="val">
                    RM {{ number_format($quotation->discount_amount, 2) }}
                </td>
            </tr>

            @endif

            @if($quotation->sst_amount > 0)

            <tr>
                <td class="lbl">
                    SST ({{ $quotation->sst_rate }}%)
                </td>

                <td class="val">
                    RM {{ number_format($quotation->sst_amount, 2) }}
                </td>
            </tr>

            @endif

            <tr class="total-row">

                <td>
                    JUMLAH KESELURUHAN
                </td>

                <td>
                    RM {{ number_format($quotation->total_amount, 2) }}
                </td>

            </tr>

        </table>

    </div>

</div>

{{-- ───────────────── TERMS ───────────────── --}}

@if($quotation->terms_conditions)

<div class="terms-block">

    <div class="terms-title">
        Terma & Syarat
    </div>

    <div class="terms-content">
        {{ $quotation->terms_conditions }}
    </div>

</div>

@endif

{{-- ───────────────── SIGNATURE ───────────────── --}}

<div class="signature-block">

    <div class="sig-left">

        <div class="sig-line"></div>

        <div class="sig-label">

            <strong>{{ $company->name }}</strong><br>

            Disediakan oleh

        </div>

    </div>

    <div class="sig-right">

        <div class="customer-sign-box">

            <div class="customer-sign-title">
                Tandatangan & Cop Pelanggan
            </div>

            <div class="customer-sign-line"></div>

            <div class="sig-label">

                {{ $quotation->customer_name }}

                <br>

                Tarikh: _______________

            </div>

        </div>

    </div>

</div>

{{-- ───────────────── FOOTER ───────────────── --}}

<div class="footer">

    Sebut Harga ini dijana secara automatik oleh sistem
    SAGA SME

    &bull;

    {{ $quotation->quotation_number }}

    &bull;

    {{ now()->format('d/m/Y H:i') }}

</div>

</body>
</html>