<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Billplz Sandbox Payment</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; background: #f3f4f6; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
    .card { background: #fff; border-radius: 12px; box-shadow: 0 4px 24px rgba(0,0,0,0.10); width: 420px; overflow: hidden; }
    .header { background: #e63946; padding: 20px 24px; }
    .header .sandbox-badge { display: inline-block; background: #fff; color: #e63946; font-size: 10px; font-weight: bold; padding: 2px 8px; border-radius: 20px; margin-left: 10px; vertical-align: middle; }
    .header h2 { color: #fff; font-size: 13px; margin-top: 6px; opacity: 0.85; }
    .body { padding: 24px; }
    .amount-box { text-align: center; margin-bottom: 20px; padding: 16px; background: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb; }
    .amount-box .label { font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 1px; }
    .amount-box .amount { font-size: 32px; font-weight: bold; color: #111; margin-top: 4px; }
    .amount-box .desc { font-size: 12px; color: #6b7280; margin-top: 4px; }
    .info-row { display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 8px; }
    .info-row .ilabel { color: #6b7280; }
    .info-row .ivalue { font-weight: bold; color: #111; }
    .divider { border: none; border-top: 1px solid #e5e7eb; margin: 16px 0; }
    .channel-title { font-size: 11px; font-weight: bold; color: #6b7280; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px; }
    .channel-btn { width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 8px; background: #fff; cursor: pointer; display: flex; align-items: center; gap: 12px; margin-bottom: 8px; font-size: 13px; font-weight: 500; }
    .channel-btn:hover { border-color: #e63946; background: #fff5f5; }
    .channel-btn.selected { border-color: #e63946; background: #fff5f5; }
    .channel-icon { width: 36px; height: 36px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 18px; }
    .fpx { background: #fff3e0; }
    .card-icon { background: #e3f2fd; }
    .pay-btn { width: 100%; padding: 14px; background: #e63946; color: #fff; border: none; border-radius: 8px; font-size: 15px; font-weight: bold; cursor: pointer; margin-top: 16px; }
    .pay-btn:hover { background: #c1121f; }
    .sandbox-notice { background: #fef9c3; border: 1px solid #fde68a; padding: 10px 14px; border-radius: 6px; font-size: 11px; color: #92400e; margin-bottom: 16px; text-align: center; }
    .loading { display: none; text-align: center; padding: 20px; }
    .spinner { width: 36px; height: 36px; border: 4px solid #e5e7eb; border-top-color: #e63946; border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto 12px; }
    @keyframes spin { to { transform: rotate(360deg); } }
</style>
</head>
<body>
<div class="card">
    <div class="header">
        <div>
            <strong style="color:#fff; font-size:20px; letter-spacing:1px;">billplz</strong>
            <span class="sandbox-badge">SANDBOX</span>
        </div>
        <h2>Secure Payment Gateway</h2>
    </div>
    <div class="body">
        <div class="sandbox-notice">
            ‚ö†Ô∏è <strong>SANDBOX MODE</strong> ‚Äî Simulasi pembayaran. Tiada transaksi sebenar.
        </div>
        <div class="amount-box">
            <div class="label">Jumlah Bayaran</div>
            <div class="amount">MYR {{ number_format($bill->amount, 2) }}</div>
            <div class="desc">{{ $bill->description }}</div>
        </div>
        <div class="info-row"><span class="ilabel">Bill ID</span><span class="ivalue">{{ $bill->billplz_id }}</span></div>
        <div class="info-row"><span class="ilabel">Penerima</span><span class="ivalue">{{ $bill->payer_name }}</span></div>
        <div class="info-row"><span class="ilabel">Rujukan</span><span class="ivalue">{{ $bill->reference_no }}</span></div>
        <hr class="divider">
        <div class="channel-title">Pilih Kaedah Pembayaran</div>
        <button class="channel-btn selected" onclick="selectChannel(this, 'fpx')">
            <div class="channel-icon fpx">Ìø¶</div>
            <div><div>FPX ‚Äî Online Banking</div><div style="font-size:11px;color:#6b7280;">Maybank, CIMB, RHB, dll.</div></div>
        </button>
        <button class="channel-btn" onclick="selectChannel(this, 'card')">
            <div class="channel-icon card-icon">Ì≤≥</div>
            <div><div>Kad Kredit / Debit</div><div style="font-size:11px;color:#6b7280;">Visa, Mastercard</div></div>
        </button>
        <form method="POST" action="{{ route('billplz.mock.pay', $bill->billplz_id) }}" id="pay-form">
            @csrf
            <input type="hidden" name="channel" id="channel-input" value="fpx">
            <input type="hidden" name="simulate" value="success">
            <button type="submit" class="pay-btn">Bayar Sekarang ‚Äî MYR {{ number_format($bill->amount, 2) }}</button>
        </form>
        <form method="POST" action="{{ route('billplz.mock.pay', $bill->billplz_id) }}" style="margin-top:8px;">
            @csrf
            <input type="hidden" name="channel" value="fpx">
            <input type="hidden" name="simulate" value="failed">
            <button type="submit" style="width:100%;padding:10px;background:#fff;border:1px solid #e5e7eb;border-radius:8px;color:#6b7280;font-size:12px;cursor:pointer;">Simulate Gagal / Batalkan</button>
        </form>
        <div class="loading" id="loading">
            <div class="spinner"></div>
            <div style="font-size:13px;color:#6b7280;">Memproses pembayaran...</div>
        </div>
    </div>
</div>
<script>
function selectChannel(btn, channel) {
    document.querySelectorAll('.channel-btn').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
    document.getElementById('channel-input').value = channel;
}
document.getElementById('pay-form').addEventListener('submit', function() {
    document.getElementById('loading').style.display = 'block';
    setTimeout(function() {
        if (window.opener) { window.opener.location.reload(); }
        window.close();
    }, 2500);
});
</script>
</body>
</html>
