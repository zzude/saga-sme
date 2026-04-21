<x-filament-panels::page>

<div style="margin-bottom:1.5rem;">
    {{ $this->form }}
</div>

@if (!empty($result))

@php
    $sections = [
        ['label' => 'ASSETS',      'color_bg' => '#dbeafe', 'color_text' => '#1d4ed8', 'color_border' => '#93c5fd', 'rows' => $result['assets'],      'total' => $result['total_asset'],     'total_label' => 'Total Assets'],
        ['label' => 'LIABILITIES', 'color_bg' => '#fef3c7', 'color_text' => '#b45309', 'color_border' => '#fcd34d', 'rows' => $result['liabilities'],  'total' => $result['total_liability'], 'total_label' => 'Total Liabilities'],
        ['label' => 'EQUITY',      'color_bg' => '#ede9fe', 'color_text' => '#7c3aed', 'color_border' => '#c4b5fd', 'rows' => $result['equity'],       'total' => $result['total_equity'],    'total_label' => 'Total Equity (incl. Net Profit)'],
    ];
@endphp

<div style="display:flex;flex-direction:column;gap:1rem;">
@foreach($sections as $section)
<div style="border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
    <div style="padding:10px 16px;background:{{ $section['color_bg'] }};border-bottom:1px solid #e5e7eb;">
        <span style="font-weight:700;color:{{ $section['color_text'] }};font-size:13px;">{{ $section['label'] }}</span>
    </div>
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <tbody>
            @foreach($section['rows'] as $row)
            <tr style="border-bottom:1px solid #f3f4f6;">
                <td style="padding:7px 16px;font-family:monospace;font-size:11px;color:#9ca3af;width:90px;">{{ $row['code'] }}</td>
                <td style="padding:7px 16px;color:#111827;">{{ $row['name'] }}</td>
                <td style="padding:7px 16px;text-align:right;font-family:monospace;font-weight:600;color:{{ $section['color_text'] }};">{{ number_format($row['amount'], 2) }}</td>
            </tr>
            @endforeach
            @if($section['label'] === 'EQUITY')
            <tr style="border-bottom:1px solid #f3f4f6;background:#fafafa;">
                <td style="padding:7px 16px;font-family:monospace;font-size:11px;color:#9ca3af;"></td>
                <td style="padding:7px 16px;color:#6b7280;font-style:italic;">Current Period Net Profit</td>
                <td style="padding:7px 16px;text-align:right;font-family:monospace;font-weight:600;color:{{ $result['net_profit'] >= 0 ? '#15803d' : '#dc2626' }};">{{ number_format($result['net_profit'], 2) }}</td>
            </tr>
            @endif
        </tbody>
        <tfoot>
            <tr style="background:{{ $section['color_bg'] }};border-top:2px solid {{ $section['color_border'] }};">
                <td colspan="2" style="padding:10px 16px;font-weight:700;color:{{ $section['color_text'] }};">{{ $section['total_label'] }}</td>
                <td style="padding:10px 16px;text-align:right;font-family:monospace;font-weight:700;color:{{ $section['color_text'] }};">{{ number_format($section['total'], 2) }}</td>
            </tr>
        </tfoot>
    </table>
</div>
@endforeach

{{-- BALANCE CHECK --}}
<div style="border:2px solid {{ $result['is_balanced'] ? '#16a34a' : '#dc2626' }};border-radius:8px;padding:14px 20px;display:flex;justify-content:space-between;align-items:center;background:{{ $result['is_balanced'] ? '#f0fdf4' : '#fef2f2' }};">
    <span style="font-size:13px;font-weight:700;color:{{ $result['is_balanced'] ? '#15803d' : '#dc2626' }};">
        ASSETS = LIABILITIES + EQUITY &nbsp;
        {{ $result['is_balanced'] ? '✓ Balanced' : '✗ Not Balanced' }}
    </span>
    <span style="font-size:15px;font-weight:800;font-family:monospace;color:#374151;">
        MYR {{ number_format($result['total_asset'], 2) }}
    </span>
</div>
</div>

@else
<div style="margin-top:2rem;text-align:center;color:#9ca3af;padding:3rem 0;">
    Select a period above to generate the Balance Sheet.
</div>
@endif

</x-filament-panels::page>
