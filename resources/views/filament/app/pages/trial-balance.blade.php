<x-filament-panels::page>

<div style="margin-bottom:1.5rem;">
    {{ $this->form }}
</div>

@if (!empty($result['lines']))
<div style="border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">

    <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 16px;border-bottom:1px solid #e5e7eb;background:#f9fafb;">
        <span style="font-size:12px;color:#6b7280;">{{ count($result['lines']) }} accounts</span>
        @if($result['is_balanced'])
            <span style="font-size:11px;font-weight:600;padding:3px 10px;border-radius:999px;background:#dcfce7;color:#15803d;">✓ Balanced</span>
        @else
            <span style="font-size:11px;font-weight:600;padding:3px 10px;border-radius:999px;background:#fee2e2;color:#dc2626;">✗ Not Balanced</span>
        @endif
    </div>

    <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
            <tr style="background:#f9fafb;">
                <th style="padding:10px 16px;text-align:left;font-weight:600;color:#374151;width:90px;border-bottom:1px solid #e5e7eb;">Code</th>
                <th style="padding:10px 16px;text-align:left;font-weight:600;color:#374151;border-bottom:1px solid #e5e7eb;">Account Name</th>
                <th style="padding:10px 16px;text-align:left;font-weight:600;color:#374151;width:100px;border-bottom:1px solid #e5e7eb;">Type</th>
                <th style="padding:10px 16px;text-align:right;font-weight:600;color:#374151;width:130px;border-bottom:1px solid #e5e7eb;">Debit (MYR)</th>
                <th style="padding:10px 16px;text-align:right;font-weight:600;color:#374151;width:130px;border-bottom:1px solid #e5e7eb;">Credit (MYR)</th>
                <th style="padding:10px 16px;text-align:right;font-weight:600;color:#374151;width:130px;border-bottom:1px solid #e5e7eb;">Balance</th>
            </tr>
        </thead>
        <tbody>
            @foreach($result['lines'] as $line)
                @php
                    $bs = match($line['type']) {
                        'asset'     => 'background:#dbeafe;color:#1d4ed8;',
                        'liability' => 'background:#fef3c7;color:#b45309;',
                        'equity'    => 'background:#ede9fe;color:#7c3aed;',
                        'revenue'   => 'background:#dcfce7;color:#15803d;',
                        'expense'   => 'background:#fee2e2;color:#dc2626;',
                        default     => 'background:#f3f4f6;color:#374151;',
                    };
                @endphp
                <tr style="border-bottom:1px solid #f3f4f6;">
                    <td style="padding:8px 16px;font-family:monospace;font-size:11px;color:#9ca3af;">{{ $line['code'] }}</td>
                    <td style="padding:8px 16px;color:#111827;">{{ $line['name'] }}</td>
                    <td style="padding:8px 16px;">
                        <span style="font-size:11px;font-weight:500;padding:2px 8px;border-radius:4px;{{ $bs }}">{{ ucfirst($line['type']) }}</span>
                    </td>
                    <td style="padding:8px 16px;text-align:right;font-family:monospace;color:#374151;">{{ $line['debit'] > 0 ? number_format($line['debit'], 2) : '—' }}</td>
                    <td style="padding:8px 16px;text-align:right;font-family:monospace;color:#374151;">{{ $line['credit'] > 0 ? number_format($line['credit'], 2) : '—' }}</td>
                    <td style="padding:8px 16px;text-align:right;font-family:monospace;font-weight:600;color:{{ $line['balance'] >= 0 ? '#16a34a' : '#dc2626' }};">
                        {{ number_format(abs($line['balance']), 2) }}{{ $line['balance'] < 0 ? ' (Cr)' : '' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background:#f9fafb;border-top:2px solid #d1d5db;">
                <td colspan="3" style="padding:10px 16px;text-align:right;font-weight:700;color:#374151;">TOTAL</td>
                <td style="padding:10px 16px;text-align:right;font-family:monospace;font-weight:700;color:#111827;">{{ number_format($result['total_debit'], 2) }}</td>
                <td style="padding:10px 16px;text-align:right;font-family:monospace;font-weight:700;color:#111827;">{{ number_format($result['total_credit'], 2) }}</td>
                <td style="padding:10px 16px;"></td>
            </tr>
        </tfoot>
    </table>
</div>

@else
<div style="margin-top:2rem;text-align:center;color:#9ca3af;padding:3rem 0;">
    Select a period above to generate the Trial Balance.
</div>
@endif

</x-filament-panels::page>