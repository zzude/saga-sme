<x-filament-panels::page>

<div style="margin-bottom:1.5rem;">{{ $this->form }}</div>

{{-- ACTION BUTTONS --}}
<div style="display:flex;gap:0.75rem;margin-bottom:1.5rem;flex-wrap:wrap;">
    <x-filament::button wire:click="previewImport" color="warning" icon="heroicon-o-eye">
        Preview Import
    </x-filament::button>

    @if(!empty($preview) && $preview['can_import'])
    <x-filament::button wire:click="commitImport" color="success" icon="heroicon-o-check-circle">
        Confirm Import ({{ $preview['ok_count'] }} accounts)
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
            <span style="color:#15803d;font-weight:600;font-size:13px;">✓ {{ $preview['ok_count'] }} ready</span>
            <span style="color:#dc2626;font-weight:600;font-size:13px;">✗ {{ $preview['err_count'] }} errors</span>
        </div>
    </div>
    <table style="width:100%;border-collapse:collapse;font-size:12px;">
        <thead>
            <tr style="background:#f9fafb;">
                <th style="padding:8px 12px;text-align:left;border-bottom:1px solid #e5e7eb;">Row</th>
                <th style="padding:8px 12px;text-align:left;border-bottom:1px solid #e5e7eb;">Code</th>
                <th style="padding:8px 12px;text-align:left;border-bottom:1px solid #e5e7eb;">Name</th>
                <th style="padding:8px 12px;text-align:left;border-bottom:1px solid #e5e7eb;">Type</th>
                <th style="padding:8px 12px;text-align:left;border-bottom:1px solid #e5e7eb;">Level</th>
                <th style="padding:8px 12px;text-align:right;border-bottom:1px solid #e5e7eb;">Opening Bal</th>
                <th style="padding:8px 12px;text-align:left;border-bottom:1px solid #e5e7eb;">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($preview['preview'] as $row)
            <tr style="border-bottom:1px solid #f3f4f6;background:{{ $row['status'] === 'error' ? '#fff5f5' : 'white' }}">
                <td style="padding:6px 12px;color:#9ca3af;">{{ $row['row'] }}</td>
                <td style="padding:6px 12px;font-family:monospace;">{{ $row['code'] }}</td>
                <td style="padding:6px 12px;">{{ $row['name'] }}</td>
                <td style="padding:6px 12px;">{{ $row['type'] }}</td>
                <td style="padding:6px 12px;text-align:center;">{{ $row['level'] }}</td>
                <td style="padding:6px 12px;text-align:right;font-family:monospace;">
                    {{ $row['ob'] !== null ? number_format($row['ob'], 2) : '—' }}
                </td>
                <td style="padding:6px 12px;">
                    @if($row['status'] === 'ok')
                        <span style="color:#15803d;font-size:11px;font-weight:600;">✓ OK</span>
                    @else
                        <span style="color:#dc2626;font-size:11px;">✗ {{ implode('; ', $row['errors']) }}</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- FORMAT GUIDE --}}
<div style="padding:12px 16px;background:#f0f9ff;border-radius:8px;border:1px solid #bae6fd;">
    <div style="font-weight:600;font-size:12px;color:#0369a1;margin-bottom:6px;">CSV Format:</div>
    <code style="font-size:11px;color:#374151;">code, name, type, parent_code, description, is_active, opening_balance</code>
    <div style="font-size:11px;color:#6b7280;margin-top:4px;">
        type: asset / liability / equity / revenue / expense &nbsp;|&nbsp;
        level auto-derived from parent &nbsp;|&nbsp;
        opening_balance optional (numeric)
    </div>
</div>

</x-filament-panels::page>