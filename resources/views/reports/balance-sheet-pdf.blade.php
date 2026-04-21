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
    .balance-row td { border-top: 2px solid #000; border-bottom: 2px solid #000; font-weight: bold; font-size: 9pt; }
</style>
</head>
<body>
    <div class="header">
        <h2>BALANCE SHEET</h2>
        <p>{{ $companyName }}</p>
        <p>Period: {{ $periodName }}</p>
        <p>Generated: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    @foreach([['title'=>'ASSETS','rows'=>$result['assets'],'total'=>$result['total_asset'],'label'=>'Total Assets'],['title'=>'LIABILITIES','rows'=>$result['liabilities'],'total'=>$result['total_liability'],'label'=>'Total Liabilities'],['title'=>'EQUITY','rows'=>$result['equity'],'total'=>$result['total_equity'],'label'=>'Total Equity']] as $section)
    <div class="section-title">{{ $section['title'] }}</div>
    <table>
        <tbody>
            @foreach($section['rows'] as $row)
            <tr><td style="width:15%">{{ $row['code'] }}</td><td>{{ $row['name'] }}</td><td class="text-right" style="width:25%">{{ number_format($row['amount'], 2) }}</td></tr>
            @endforeach
            @if($section['title'] === 'EQUITY')
            <tr><td></td><td><i>Current Period Net Profit</i></td><td class="text-right">{{ number_format($result['net_profit'], 2) }}</td></tr>
            @endif
        </tbody>
        <tfoot>
            <tr class="total-row"><td colspan="2" class="text-right">{{ $section['label'] }}</td><td class="text-right">{{ number_format($section['total'], 2) }}</td></tr>
        </tfoot>
    </table>
    @endforeach

    <table style="margin-top:4mm;">
        <tr class="balance-row">
            <td>ASSETS = LIABILITIES + EQUITY &nbsp; {{ $result['is_balanced'] ? '[BALANCED]' : '[NOT BALANCED]' }}</td>
            <td class="text-right">MYR {{ number_format($result['total_asset'], 2) }}</td>
        </tr>
    </table>
</body>
</html>
