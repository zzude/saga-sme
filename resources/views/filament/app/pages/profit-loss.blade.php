<x-filament-panels::page>

<div style="margin-bottom:1.5rem;">
    {{ $this->form }}
</div>

@if (!empty($result))
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">

    {{-- REVENUE --}}
    <div style="border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
        <div style="padding:10px 16px;background:#f0fdf4;border-bottom:1px solid #e5e7eb;">
            <span style="font-weight:700;color:#15803d;font-size:13px;">REVENUE</span>
        </div>
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <tbody>
                @foreach($result['revenues'] as $row)
                <tr style="border-bottom:1px solid #f3f4f6;">
                    <td style="padding:7px 16px;font-family:monospace;font-size:11px;color:#9ca3af;width:90px;">{{ $row['code'] }}</td>
                    <td style="padding:7px 16px;color:#111827;">{{ $row['name'] }}</td>
                    <td style="padding:7px 16px;text-align:right;font-family:monospace;color:#15803d;font-weight:600;">{{ number_format($row['amount'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background:#f0fdf4;border-top:2px solid #bbf7d0;">
                    <td colspan="2" style="padding:10px 16px;font-weight:700;color:#15803d;">Total Revenue</td>
                    <td style="padding:10px 16px;text-align:right;font-family:monospace;font-weight:700;color:#15803d;">{{ number_format($result['total_revenue'], 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- EXPENSES --}}
    <div style="border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
        <div style="padding:10px 16px;background:#fff7ed;border-bottom:1px solid #e5e7eb;">
            <span style="font-weight:700;color:#c2410c;font-size:13px;">EXPENSES</span>
        </div>
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <tbody>
                @foreach($result['expenses'] as $row)
                <tr style="border-bottom:1px solid #f3f4f6;">
                    <td style="padding:7px 16px;font-family:monospace;font-size:11px;color:#9ca3af;width:90px;">{{ $row['code'] }}</td>
                    <td style="padding:7px 16px;color:#111827;">{{ $row['name'] }}</td>
                    <td style="padding:7px 16px;text-align:right;font-family:monospace;color:#c2410c;font-weight:600;">{{ number_format($row['amount'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background:#fff7ed;border-top:2px solid #fed7aa;">
                    <td colspan="2" style="padding:10px 16px;font-weight:700;color:#c2410c;">Total Expenses</td>
                    <td style="padding:10px 16px;text-align:right;font-family:monospace;font-weight:700;color:#c2410c;">{{ number_format($result['total_expense'], 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

{{-- NET PROFIT --}}
@php $profit = $result['net_profit']; @endphp
<div style="border:2px solid {{ $profit >= 0 ? '#16a34a' : '#dc2626' }};border-radius:8px;padding:16px 20px;display:flex;justify-content:space-between;align-items:center;background:{{ $profit >= 0 ? '#f0fdf4' : '#fef2f2' }};">
    <span style="font-size:15px;font-weight:700;color:{{ $profit >= 0 ? '#15803d' : '#dc2626' }};">
        {{ $profit >= 0 ? 'NET PROFIT' : 'NET LOSS' }}
    </span>
    <span style="font-size:18px;font-weight:800;font-family:monospace;color:{{ $profit >= 0 ? '#15803d' : '#dc2626' }};">
        MYR {{ number_format(abs($profit), 2) }}
    </span>
</div>

@else
<div style="margin-top:2rem;text-align:center;color:#9ca3af;padding:3rem 0;">
    Select a period above to generate the Profit & Loss Statement.
</div>
@endif

</x-filament-panels::page>
