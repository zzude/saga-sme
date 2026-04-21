<x-filament-panels::page>

<div style="margin-bottom:1.5rem;">{{ $this->form }}</div>

{{-- ACTION BUTTONS --}}
<div style="display:flex;gap:0.75rem;margin-bottom:1.5rem;flex-wrap:wrap;">
    <x-filament::button wire:click="previewImport" color="warning" icon="heroicon-o-eye">
        Preview Import
    </x-filament::button>

    @if(!empty($preview) && $preview['can_import'])
    <x-filament::button wire:click="commitImport" color="success" icon="heroicon-o-check-circle">
        Confirm Import ({{ $preview['ok_count'] }} entries)
    </x-filament::button>
    @endif

    @if(!empty($preview['errors']))
    <x-filament::button wire:click="downloadErrors" color="danger" icon="heroicon-o-arrow-down-tray">
        Download Errors CSV
    </x-filament::button>
    @endif
</div>

{{-- PREVIEW TABLE --}}
@if($previewed && !empty($preview))
<div style="border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;margin-bottom:1.5rem;">
    <div style="padding:10px 16px;background:#f9fafb;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center;">
        <span style="font-weight:600;font-size:13px;">Preview Results</span>
        <div style="display:flex;gap:1rem;">
            <span style="color:#15803d;font-weight:600;font-size:13px;">✓ {{ $preview['ok_count'] }} entries ready</span>
            <span style="color:#dc2626;font-weight:600;font-size:13px;">✗ {{ $preview['err_count'] }} errors</span>
        </div>
    </div>

    @foreach($preview['entries'] as $entry)
    <div style="border-bottom:1px solid #e5e7eb;padding:10px 16px;background:{{ $entry['status'] === 'error' ? '#fff5f5' : 'white' }}">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
            <span style="font-weight:600;font-size:12px;font-family:monospace;">{{ $entry['reference_no'] }}</span>
            <div style="display:flex;gap:1rem;font-size:11px;">
                <span style="color:#6b7280;">{{ $entry['date'] }}</span>
                <span style="color:#6b7280;">DR: {{ number_format($entry['total_debit'], 2) }}</span>
                <span style="color:#6b7280;">CR: {{ number_format($entry['total_credit'], 2) }}</span>
                @if($entry['status'] === 'ok')
                    <span style="color:#15803d;font-weight:600;">✓ Balanced</span>
                @else
                    <span style="color:#dc2626;font-weight:600;">✗ Error</span>
                @endif
            </div>
        </div>
        <div style="font-size:11px;color:#6b7280;margin-bottom:4px;">{{ $entry['summary_text'] }}</div>

        @if(!empty($entry['errors']))
            @foreach($entry['errors'] as $err)
            <div style="font-size:11px;color:#dc2626;">✗ {{ $err }}</div>
            @endforeach
        @endif

        <table style="width:100%;font-size:11px;margin-top:6px;">
            @foreach($entry['lines'] as $line)
            <tr>
                <td style="padding:2px 0;color:#9ca3af;width:80px;font-family:monospace;">{{ $line['account_code'] }}</td>
                <td style="padding:2px 0;color:#374151;">{{ $line['account_name'] ?? '—' }}</td>
                <td style="padding:2px 0;text-align:right;width:100px;font-family:monospace;color:#1d4ed8;">
                    {{ $line['debit'] > 0 ? 'DR ' . number_format($line['debit'], 2) : '' }}
                </td>
                <td style="padding:2px 0;text-align:right;width:100px;font-family:monospace;color:#15803d;">
                    {{ $line['credit'] > 0 ? 'CR ' . number_format($line['credit'], 2) : '' }}
                </td>
            </tr>
            @endforeach
        </table>
    </div>
    @endforeach
</div>
@endif

{{-- FORMAT GUIDE --}}
<div style="padding:12px 16px;background:#f0f9ff;border-radius:8px;border:1px solid #bae6fd;">
    <div style="font-weight:600;font-size:12px;color:#0369a1;margin-bottom:6px;">CSV Format:</div>
    <code style="font-size:11px;color:#374151;">reference_no, date, summary_text, account_code, debit, credit, description</code>
    <div style="font-size:11px;color:#6b7280;margin-top:4px;">
        Same reference_no = same journal entry &nbsp;|&nbsp;
        date format: YYYY-MM-DD &nbsp;|&nbsp;
        DR = CR required per entry
    </div>
</div>

</x-filament-panels::page>