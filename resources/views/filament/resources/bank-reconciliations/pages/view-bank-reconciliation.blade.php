<x-filament-panels::page>

    {{-- Header Info --}}
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;margin-bottom:1.5rem;">
        <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:1rem;">
            <div style="font-size:11px;color:#6b7280;margin-bottom:4px;">BANK ACCOUNT</div>
            <div style="font-weight:600;">{{ $this->record->account->name }}</div>
        </div>
        <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:1rem;">
            <div style="font-size:11px;color:#6b7280;margin-bottom:4px;">STATEMENT BALANCE</div>
            <div style="font-weight:600;">MYR {{ number_format($this->record->statement_balance, 2) }}</div>
        </div>
        <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:1rem;">
            <div style="font-size:11px;color:#6b7280;margin-bottom:4px;">CLEARED BALANCE</div>
            <div style="font-weight:600;">MYR {{ number_format($clearedBalance, 2) }}</div>
        </div>
    </div>

    {{-- Difference Banner --}}
    @if(abs($difference) < 0.01)
        <div style="background:#dcfce7;border:1px solid #86efac;border-radius:8px;padding:1rem;margin-bottom:1.5rem;text-align:center;font-weight:600;color:#15803d;">
            ✓ RECONCILED — Difference: MYR 0.00
        </div>
    @else
        <div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:8px;padding:1rem;margin-bottom:1.5rem;text-align:center;font-weight:600;color:#dc2626;">
            ✗ NOT RECONCILED — Difference: MYR {{ number_format($difference, 2) }}
        </div>
    @endif

    {{-- GL Lines Table --}}
    <div style="border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
        <div style="padding:12px 16px;background:#f9fafb;border-bottom:1px solid #e5e7eb;font-weight:600;font-size:13px;">
            GL Transactions — {{ $this->record->account->name }}
            <span style="font-weight:400;color:#6b7280;margin-left:8px;">{{ count($glLines) }} transactions</span>
        </div>
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead>
                <tr style="background:#f9fafb;">
                    <th style="padding:10px 16px;text-align:center;width:50px;border-bottom:1px solid #e5e7eb;">✓</th>
                    <th style="padding:10px 16px;text-align:left;border-bottom:1px solid #e5e7eb;">Date</th>
                    <th style="padding:10px 16px;text-align:left;border-bottom:1px solid #e5e7eb;">Reference</th>
                    <th style="padding:10px 16px;text-align:left;border-bottom:1px solid #e5e7eb;">Description</th>
                    <th style="padding:10px 16px;text-align:right;border-bottom:1px solid #e5e7eb;">Debit (MYR)</th>
                    <th style="padding:10px 16px;text-align:right;border-bottom:1px solid #e5e7eb;">Credit (MYR)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($glLines as $line)
                    <tr style="border-bottom:1px solid #f3f4f6;{{ $line['cleared'] ? 'background:#f0fdf4;' : '' }}"
                        wire:click="toggleLine({{ $line['id'] }})"
                        style="cursor:pointer;">
                        <td style="padding:8px 16px;text-align:center;">
                            @if($line['cleared'])
                                <span style="color:#16a34a;font-size:16px;">✓</span>
                            @else
                                <span style="color:#d1d5db;font-size:16px;">○</span>
                            @endif
                        </td>
                        <td style="padding:8px 16px;color:#6b7280;">{{ $line['date'] }}</td>
                        <td style="padding:8px 16px;font-family:monospace;font-size:11px;color:#9ca3af;">{{ $line['reference_no'] }}</td>
                        <td style="padding:8px 16px;">{{ $line['description'] }}</td>
                        <td style="padding:8px 16px;text-align:right;font-family:monospace;">
                            {{ $line['debit'] > 0 ? number_format($line['debit'], 2) : '—' }}
                        </td>
                        <td style="padding:8px 16px;text-align:right;font-family:monospace;">
                            {{ $line['credit'] > 0 ? number_format($line['credit'], 2) : '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="padding:2rem;text-align:center;color:#9ca3af;">
                            No GL transactions found for this account and period.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</x-filament-panels::page>