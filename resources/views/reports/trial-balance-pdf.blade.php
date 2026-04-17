<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Consolas, monospace; font-size: 8pt; }
    .header { text-align: center; margin-bottom: 6mm; }
    .header h2 { font-size: 10pt; font-weight: bold; }
    .header p { font-size: 8pt; }
    table { width: 100%; border-collapse: collapse; }
    th { border-bottom: 1px solid #000; border-top: 1px solid #000; padding: 1.5mm 2mm; text-align: left; font-weight: bold; }
    td { padding: 1mm 2mm; }
    .text-right { text-align: right; }
    .total-row td { border-top: 1px solid #000; border-bottom: 2px solid #000; font-weight: bold; }
    .balanced { color: #000; }
</style>
</head>
<body>
    <div class="header">
        <h2>TRIAL BALANCE</h2>
        <p>{{ $companyName }}</p>
        <p>Period: {{ $periodName }}</p>
        <p>Generated: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:12%">Code</th>
                <th style="width:38%">Account Name</th>
                <th style="width:12%">Type</th>
                <th class="text-right" style="width:19%">Debit (MYR)</th>
                <th class="text-right" style="width:19%">Credit (MYR)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lines as $line)
            <tr>
                <td>{{ $line['code'] }}</td>
                <td>{{ $line['name'] }}</td>
                <td>{{ ucfirst($line['type']) }}</td>
                <td class="text-right">{{ $line['debit'] > 0 ? number_format($line['debit'], 2) : '-' }}</td>
                <td class="text-right">{{ $line['credit'] > 0 ? number_format($line['credit'], 2) : '-' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="3" class="text-right">TOTAL</td>
                <td class="text-right">{{ number_format($totalDebit, 2) }}</td>
                <td class="text-right">
                    {{ number_format($totalCredit, 2) }}
                    &nbsp;&nbsp;
                    @if($isBalanced) [BALANCED] @else [NOT BALANCED] @endif
                </td>
            </tr>
        </tfoot>
    </table>
</body>
</html>