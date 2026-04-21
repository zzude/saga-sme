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
    .section-title { font-weight: bold; font-size: 9pt; padding: 2mm 0; border-bottom: 1px solid #000; margin-top: 4mm; margin-bottom: 1mm; }
    table { width: 100%; border-collapse: collapse; }
    td { padding: 1mm 2mm; }
    .text-right { text-align: right; }
    .total-row td { border-top: 1px solid #000; font-weight: bold; }
    .net-row td { border-top: 2px solid #000; border-bottom: 2px solid #000; font-weight: bold; font-size: 9pt; }
</style>
</head>
<body>
    <div class="header">
        <h2>PROFIT & LOSS STATEMENT</h2>
        <p>{{ $companyName }}</p>
        <p>Period: {{ $periodName }}</p>
        <p>Generated: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <div class="section-title">REVENUE</div>
    <table>
        <tbody>
            @foreach($result['revenues'] as $row)
            <tr><td style="width:15%">{{ $row['code'] }}</td><td>{{ $row['name'] }}</td><td class="text-right" style="width:25%">{{ number_format($row['amount'], 2) }}</td></tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row"><td colspan="2" class="text-right">Total Revenue</td><td class="text-right">{{ number_format($result['total_revenue'], 2) }}</td></tr>
        </tfoot>
    </table>

    <div class="section-title">EXPENSES</div>
    <table>
        <tbody>
            @foreach($result['expenses'] as $row)
            <tr><td style="width:15%">{{ $row['code'] }}</td><td>{{ $row['name'] }}</td><td class="text-right" style="width:25%">{{ number_format($row['amount'], 2) }}</td></tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row"><td colspan="2" class="text-right">Total Expenses</td><td class="text-right">{{ number_format($result['total_expense'], 2) }}</td></tr>
        </tfoot>
    </table>

    <table style="margin-top:4mm;">
        <tr class="net-row">
            <td>{{ $result['net_profit'] >= 0 ? 'NET PROFIT' : 'NET LOSS' }}</td>
            <td class="text-right">MYR {{ number_format(abs($result['net_profit']), 2) }}</td>
        </tr>
    </table>
</body>
</html>
