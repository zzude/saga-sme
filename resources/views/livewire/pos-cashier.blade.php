<div class="pos-wrapper" style="display:flex; height:100vh; overflow:hidden;">

    {{-- ═══════════════════════════════════════════════════════════════════════
         OPEN SESSION MODAL
    ════════════════════════════════════════════════════════════════════════ --}}
    @if($showOpenSession)
    <div style="position:fixed;inset:0;background:rgba(0,0,0,0.6);display:flex;align-items:center;justify-content:center;z-index:999;">
        <div style="background:#fff;border-radius:12px;padding:32px;width:400px;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
            <h2 style="margin:0 0 8px;font-size:1.4rem;color:#1e293b;">🏪 Buka Sesi POS</h2>
            <p style="margin:0 0 24px;color:#64748b;font-size:0.9rem;">Masukkan wang tunai pembuka dalam laci.</p>
            <label style="display:block;font-size:0.85rem;font-weight:600;color:#374151;margin-bottom:6px;">Wang Tunai Pembuka (RM)</label>
            <input type="number" wire:model="openingCash" step="0.01" min="0"
                style="width:100%;padding:10px 14px;border:1px solid #d1d5db;border-radius:8px;font-size:1rem;margin-bottom:20px;">
            <button wire:click="openSession"
                style="width:100%;padding:12px;background:#16a34a;color:#fff;border:none;border-radius:8px;font-size:1rem;font-weight:600;cursor:pointer;">
                Buka Sesi
            </button>
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════════
         RECEIPT MODAL
    ════════════════════════════════════════════════════════════════════════ --}}
    @if($showReceipt && $lastReceipt)
    <div style="position:fixed;inset:0;background:rgba(0,0,0,0.6);display:flex;align-items:center;justify-content:center;z-index:999;">
        <div style="background:#fff;border-radius:12px;padding:32px;width:380px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
            <div style="text-align:center;margin-bottom:20px;">
                <div style="font-size:2rem;">✅</div>
                <h2 style="margin:8px 0 4px;color:#16a34a;">Bayaran Berjaya!</h2>
                <p style="margin:0;color:#64748b;font-size:0.85rem;">{{ $lastReceipt['transaction_no'] }}</p>
                <p style="margin:0;color:#94a3b8;font-size:0.8rem;">{{ $lastReceipt['datetime'] }}</p>
            </div>

            <table style="width:100%;border-collapse:collapse;margin-bottom:16px;font-size:0.85rem;">
                @foreach($lastReceipt['items'] as $item)
                <tr>
                    <td style="padding:4px 0;color:#374151;">{{ $item['description'] }}</td>
                    <td style="padding:4px 0;color:#374151;text-align:center;width:40px;">×{{ $item['quantity'] }}</td>
                    <td style="padding:4px 0;color:#374151;text-align:right;">RM {{ number_format($item['total_amount'], 2) }}</td>
                </tr>
                @endforeach
            </table>

            <div style="border-top:1px solid #e5e7eb;padding-top:12px;font-size:0.85rem;">
                <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                    <span style="color:#64748b;">Subtotal</span>
                    <span>RM {{ number_format($lastReceipt['subtotal'], 2) }}</span>
                </div>
                @if($lastReceipt['tax'] > 0)
                <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                    <span style="color:#64748b;">SST</span>
                    <span>RM {{ number_format($lastReceipt['tax'], 2) }}</span>
                </div>
                @endif
                <div style="display:flex;justify-content:space-between;font-weight:700;font-size:1rem;margin:8px 0;">
                    <span>JUMLAH</span>
                    <span style="color:#16a34a;">RM {{ number_format($lastReceipt['total'], 2) }}</span>
                </div>
                @if($lastReceipt['payment_method'] === 'cash')
                <div style="display:flex;justify-content:space-between;margin-bottom:4px;color:#64748b;font-size:0.8rem;">
                    <span>Tunai Diterima</span>
                    <span>RM {{ number_format($lastReceipt['amount_tendered'], 2) }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-weight:600;color:#dc2626;">
                    <span>Baki</span>
                    <span>RM {{ number_format($lastReceipt['change'], 2) }}</span>
                </div>
                @endif
            </div>

            <button wire:click="closeReceipt"
                style="width:100%;margin-top:20px;padding:12px;background:#3b82f6;color:#fff;border:none;border-radius:8px;font-size:1rem;font-weight:600;cursor:pointer;">
                Transaksi Baru
            </button>
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════════
         CHECKOUT MODAL
    ════════════════════════════════════════════════════════════════════════ --}}
    @if($showCheckout)
    <div style="position:fixed;inset:0;background:rgba(0,0,0,0.6);display:flex;align-items:center;justify-content:center;z-index:998;">
        <div style="background:#fff;border-radius:12px;padding:32px;width:440px;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
            <h2 style="margin:0 0 20px;font-size:1.3rem;color:#1e293b;">💳 Proses Bayaran</h2>

            <div style="background:#f8fafc;border-radius:8px;padding:16px;margin-bottom:20px;text-align:center;">
                <div style="font-size:0.85rem;color:#64748b;margin-bottom:4px;">Jumlah Perlu Dibayar</div>
                <div style="font-size:2rem;font-weight:700;color:#16a34a;">RM {{ number_format($this->cartTotal, 2) }}</div>
            </div>

            <div style="margin-bottom:16px;">
                <label style="display:block;font-size:0.85rem;font-weight:600;color:#374151;margin-bottom:8px;">Kaedah Bayaran</label>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                    @foreach(['cash' => '💵 Tunai', 'card' => '💳 Kad', 'qr' => '📱 DuitNow QR', 'credit' => '📋 Kredit'] as $method => $label)
                    <button wire:click="$set('paymentMethod', '{{ $method }}')"
                        style="padding:10px;border-radius:8px;font-size:0.85rem;font-weight:600;cursor:pointer;
                            border: 2px solid {{ $paymentMethod === $method ? '#3b82f6' : '#e5e7eb' }};
                            background: {{ $paymentMethod === $method ? '#eff6ff' : '#fff' }};
                            color: {{ $paymentMethod === $method ? '#3b82f6' : '#374151' }};">
                        {{ $label }}
                    </button>
                    @endforeach
                </div>
            </div>

            @if($paymentMethod === 'cash')
            <div style="margin-bottom:16px;">
                <label style="display:block;font-size:0.85rem;font-weight:600;color:#374151;margin-bottom:6px;">Tunai Diterima (RM)</label>
                <input type="number" wire:model.live="amountTendered" step="0.50" min="0"
                    style="width:100%;padding:10px 14px;border:1px solid #d1d5db;border-radius:8px;font-size:1.2rem;font-weight:600;text-align:right;">
                @if($amountTendered >= $this->cartTotal)
                <div style="margin-top:8px;padding:8px 12px;background:#f0fdf4;border-radius:6px;display:flex;justify-content:space-between;">
                    <span style="color:#16a34a;font-weight:600;">Baki Wang</span>
                    <span style="color:#16a34a;font-weight:700;">RM {{ number_format($this->changeAmount, 2) }}</span>
                </div>
                @endif
            </div>
            @endif

            <div style="margin-bottom:16px;">
                <label style="display:block;font-size:0.85rem;font-weight:600;color:#374151;margin-bottom:6px;">Nama Pelanggan (optional)</label>
                <input type="text" wire:model="customerName" placeholder="Walk-in Customer"
                    style="width:100%;padding:10px 14px;border:1px solid #d1d5db;border-radius:8px;font-size:0.9rem;">
            </div>

            @if(session('error'))
            <div style="padding:10px 14px;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;color:#dc2626;font-size:0.85rem;margin-bottom:16px;">
                {{ session('error') }}
            </div>
            @endif

            <div style="display:flex;gap:12px;">
                <button wire:click="$set('showCheckout', false)"
                    style="flex:1;padding:12px;background:#f1f5f9;color:#64748b;border:none;border-radius:8px;font-size:0.95rem;font-weight:600;cursor:pointer;">
                    Batal
                </button>
                <button wire:click="processPayment"
                    style="flex:2;padding:12px;background:#16a34a;color:#fff;border:none;border-radius:8px;font-size:0.95rem;font-weight:600;cursor:pointer;">
                    ✓ Sahkan Bayaran
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════════
         LEFT PANEL — Item Catalog
    ════════════════════════════════════════════════════════════════════════ --}}
    <div style="flex:1;display:flex;flex-direction:column;background:#fff;border-right:1px solid #e2e8f0;">

        {{-- Header --}}
        <div style="padding:16px 20px;border-bottom:1px solid #e2e8f0;background:#1e293b;display:flex;align-items:center;justify-content:space-between;">
            <div>
                <span style="color:#fff;font-weight:700;font-size:1.1rem;">🏪 SAGA POS</span>
                @if($session)
                <span style="color:#94a3b8;font-size:0.75rem;margin-left:12px;">Sesi: {{ $session->opened_at->format('d/m/Y H:i') }}</span>
                @endif
            </div>
            <div style="display:flex;gap:8px;">
                <a href="/app" style="padding:6px 12px;background:#334155;color:#cbd5e1;border-radius:6px;font-size:0.8rem;text-decoration:none;">← Kembali</a>
                @if($session)
                <button wire:click="$set('showCloseSession', true)"
                    style="padding:6px 12px;background:#dc2626;color:#fff;border:none;border-radius:6px;font-size:0.8rem;cursor:pointer;">
                    Tutup Sesi
                </button>
                @endif
            </div>
        </div>

        {{-- Search --}}
        <div style="padding:12px 16px;background:#f8fafc;border-bottom:1px solid #e2e8f0;">
            <input type="text" wire:model.live="search" placeholder="🔍 Cari item atau kod..."
                style="width:100%;padding:10px 14px;border:1px solid #d1d5db;border-radius:8px;font-size:0.95rem;background:#fff;">
        </div>

        {{-- Item Grid --}}
        <div style="flex:1;overflow-y:auto;padding:12px;display:grid;grid-template-columns:repeat(auto-fill, minmax(140px, 1fr));gap:10px;align-content:start;">
            @foreach($this->searchResults as $item)
            <button wire:click="addToCart({{ $item->id }})"
                style="padding:12px;background:#fff;border:1px solid #e2e8f0;border-radius:10px;cursor:pointer;text-align:left;
                    transition:all 0.15s;
                    {{ $item->track_inventory && $item->current_stock <= 0 ? 'opacity:0.5;cursor:not-allowed;' : '' }}"
                {{ $item->track_inventory && $item->current_stock <= 0 ? 'disabled' : '' }}>
                <div style="font-size:1.5rem;margin-bottom:6px;">
                    {{ $item->type === 'service' ? '⚙️' : ($item->type === 'bundle' ? '📦' : '🛍️') }}
                </div>
                <div style="font-size:0.8rem;font-weight:600;color:#1e293b;margin-bottom:2px;line-height:1.2;">{{ Str::limit($item->name, 30) }}</div>
                <div style="font-size:0.75rem;color:#64748b;margin-bottom:6px;">{{ $item->code ?? '' }}</div>
                <div style="font-size:0.9rem;font-weight:700;color:#16a34a;">RM {{ number_format($item->selling_price, 2) }}</div>
                @if($item->track_inventory)
                <div style="font-size:0.7rem;margin-top:4px;color:{{ $item->current_stock <= 0 ? '#dc2626' : ($item->current_stock <= $item->reorder_level ? '#f59e0b' : '#16a34a') }};">
                    Stok: {{ number_format($item->current_stock, 0) }}
                </div>
                @endif
            </button>
            @endforeach
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════════
         RIGHT PANEL — Cart
    ════════════════════════════════════════════════════════════════════════ --}}
    <div style="width:380px;display:flex;flex-direction:column;background:#f8fafc;">

        {{-- Cart Header --}}
        <div style="padding:16px 20px;background:#3b82f6;display:flex;justify-content:space-between;align-items:center;">
            <span style="color:#fff;font-weight:700;font-size:1rem;">🛒 Troli ({{ count($cart) }} item)</span>
            @if(!empty($cart))
            <button wire:click="clearCart" style="color:#bfdbfe;background:none;border:none;font-size:0.8rem;cursor:pointer;">Kosongkan</button>
            @endif
        </div>

        {{-- Cart Items --}}
        <div style="flex:1;overflow-y:auto;padding:12px;">
            @forelse($cart as $key => $cartItem)
            <div style="background:#fff;border-radius:10px;padding:12px;margin-bottom:8px;border:1px solid #e2e8f0;">
                <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:8px;">
                    <span style="font-size:0.85rem;font-weight:600;color:#1e293b;flex:1;margin-right:8px;">{{ $cartItem['description'] }}</span>
                    <button wire:click="removeFromCart('{{ $key }}')" style="color:#dc2626;background:none;border:none;cursor:pointer;font-size:1rem;line-height:1;">×</button>
                </div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <div style="display:flex;align-items:center;gap:4px;">
                        <button wire:click="updateQty('{{ $key }}', {{ $cartItem['quantity'] - 1 }})"
                            style="width:26px;height:26px;border-radius:50%;border:1px solid #d1d5db;background:#fff;cursor:pointer;font-size:1rem;">−</button>
                        <span style="font-size:0.9rem;font-weight:600;width:30px;text-align:center;">{{ $cartItem['quantity'] }}</span>
                        <button wire:click="updateQty('{{ $key }}', {{ $cartItem['quantity'] + 1 }})"
                            style="width:26px;height:26px;border-radius:50%;border:1px solid #d1d5db;background:#fff;cursor:pointer;font-size:1rem;">+</button>
                    </div>
                    <span style="font-size:0.8rem;color:#64748b;">× RM {{ number_format($cartItem['unit_price'], 2) }}</span>
                    <span style="margin-left:auto;font-weight:700;color:#1e293b;">RM {{ number_format($cartItem['total_amount'], 2) }}</span>
                </div>
            </div>
            @empty
            <div style="text-align:center;padding:40px 20px;color:#94a3b8;">
                <div style="font-size:3rem;margin-bottom:8px;">🛒</div>
                <div style="font-size:0.9rem;">Troli kosong</div>
                <div style="font-size:0.8rem;margin-top:4px;">Pilih item dari katalog</div>
            </div>
            @endforelse
        </div>

        {{-- Cart Summary + Checkout --}}
        <div style="padding:16px;background:#fff;border-top:1px solid #e2e8f0;">
            <div style="display:flex;justify-content:space-between;margin-bottom:6px;font-size:0.85rem;">
                <span style="color:#64748b;">Subtotal</span>
                <span>RM {{ number_format($this->cartSubtotal, 2) }}</span>
            </div>
            @if($this->cartTax > 0)
            <div style="display:flex;justify-content:space-between;margin-bottom:6px;font-size:0.85rem;">
                <span style="color:#64748b;">SST</span>
                <span>RM {{ number_format($this->cartTax, 2) }}</span>
            </div>
            @endif
            <div style="display:flex;justify-content:space-between;padding:12px 0;border-top:2px solid #e2e8f0;font-size:1.1rem;font-weight:700;">
                <span>JUMLAH</span>
                <span style="color:#16a34a;">RM {{ number_format($this->cartTotal, 2) }}</span>
            </div>

            @if(session('error'))
            <div style="padding:8px 12px;background:#fef2f2;border-radius:6px;color:#dc2626;font-size:0.8rem;margin-bottom:12px;">
                {{ session('error') }}
            </div>
            @endif

            <button wire:click="proceedCheckout"
                {{ empty($cart) || !$session ? 'disabled' : '' }}
                style="width:100%;padding:14px;font-size:1rem;font-weight:700;border:none;border-radius:10px;cursor:pointer;
                    background: {{ empty($cart) || !$session ? '#e2e8f0' : '#16a34a' }};
                    color: {{ empty($cart) || !$session ? '#94a3b8' : '#fff' }};">
                {{ !$session ? 'Buka Sesi Dahulu' : (empty($cart) ? 'Tambah Item' : '✓ Teruskan Bayaran') }}
            </button>
        </div>
    </div>

    {{-- Close Session Modal --}}
    @if($showCloseSession)
    <div style="position:fixed;inset:0;background:rgba(0,0,0,0.6);display:flex;align-items:center;justify-content:center;z-index:999;">
        <div style="background:#fff;border-radius:12px;padding:32px;width:400px;">
            <h2 style="margin:0 0 8px;font-size:1.3rem;color:#1e293b;">🔒 Tutup Sesi POS</h2>
            <p style="margin:0 0 20px;color:#64748b;font-size:0.9rem;">Jumlah jualan hari ini: <strong>RM {{ number_format($session?->total_sales ?? 0, 2) }}</strong></p>
            <label style="display:block;font-size:0.85rem;font-weight:600;color:#374151;margin-bottom:6px;">Wang Tunai Penutup (RM)</label>
            <input type="number" wire:model="closingCash" step="0.01" min="0"
                style="width:100%;padding:10px 14px;border:1px solid #d1d5db;border-radius:8px;font-size:1rem;margin-bottom:20px;">
            <div style="display:flex;gap:12px;">
                <button wire:click="$set('showCloseSession', false)"
                    style="flex:1;padding:12px;background:#f1f5f9;color:#64748b;border:none;border-radius:8px;cursor:pointer;font-weight:600;">Batal</button>
                <button wire:click="confirmCloseSession"
                    style="flex:1;padding:12px;background:#dc2626;color:#fff;border:none;border-radius:8px;cursor:pointer;font-weight:600;">Tutup Sesi</button>
            </div>
        </div>
    </div>
    @endif

</div>
