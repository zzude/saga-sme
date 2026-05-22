<?php

namespace App\Livewire;

use App\Models\Item;
use App\Models\PosSession;
use App\Services\PosService;
use Livewire\Component;
use Livewire\Attributes\Computed;

class PosCashier extends Component
{
    // ── Session ──────────────────────────────────────────────────────────────
    public ?PosSession $session = null;
    public float $openingCash   = 0;
    public bool $showOpenSession = false;

    // ── Cart ─────────────────────────────────────────────────────────────────
    public array $cart = [];

    // ── Search ───────────────────────────────────────────────────────────────
    public string $search = '';

    // ── Checkout ─────────────────────────────────────────────────────────────
    public bool $showCheckout  = false;
    public string $paymentMethod = 'cash';
    public float $amountTendered = 0;
    public string $customerName  = '';
    public string $notes         = '';

    // ── Receipt ──────────────────────────────────────────────────────────────
    public bool $showReceipt    = false;
    public ?array $lastReceipt  = null;

    // ── Close session ────────────────────────────────────────────────────────
    public bool $showCloseSession = false;
    public float $closingCash     = 0;

    public function mount(): void
    {
        $this->session = app(PosService::class)->getActiveSession();
        if (!$this->session) {
            $this->showOpenSession = true;
        }
    }

    // =========================================================================
    // SESSION
    // =========================================================================
    public function openSession(): void
    {
        $this->session         = app(PosService::class)->openSession($this->openingCash);
        $this->showOpenSession = false;
    }

    public function confirmCloseSession(): void
    {
        app(PosService::class)->closeSession($this->session, $this->closingCash);
        $this->session          = null;
        $this->showCloseSession = false;
        $this->showOpenSession  = true;
        $this->cart             = [];
    }

    // =========================================================================
    // CART
    // =========================================================================
    public function addToCart(int $itemId): void
    {
        $item = Item::find($itemId);
        if (!$item) return;

        // Check stock
        if ($item->track_inventory && $item->current_stock <= 0) {
            session()->flash('error', "Stok {$item->name} habis!");
            return;
        }

        $key = 'item_' . $itemId;

        if (isset($this->cart[$key])) {
            // Increment quantity
            $newQty = $this->cart[$key]['quantity'] + 1;

            // Check stock limit
            if ($item->track_inventory && $newQty > $item->current_stock) {
                session()->flash('error', "Stok tidak mencukupi. Baki: {$item->current_stock}");
                return;
            }

            $this->cart[$key]['quantity'] = $newQty;
            $this->recalcCartItem($key);
        } else {
            $this->cart[$key] = [
                'item_id'           => $item->id,
                'description'       => $item->name,
                'unit_price'        => (float) $item->selling_price,
                'quantity'          => 1,
                'discount_percent'  => 0,
                'is_sst_applicable' => $item->is_sst_applicable,
                'sst_rate'          => (float) $item->sst_rate,
                'subtotal'          => (float) $item->selling_price,
                'sst_amount'        => 0,
                'total_amount'      => (float) $item->selling_price,
                'unit_of_measure'   => $item->unit_of_measure,
            ];
            $this->recalcCartItem($key);
        }

        $this->search = '';
    }

    public function updateQty(string $key, float $qty): void
    {
        if (!isset($this->cart[$key])) return;

        if ($qty <= 0) {
            $this->removeFromCart($key);
            return;
        }

        $this->cart[$key]['quantity'] = $qty;
        $this->recalcCartItem($key);
    }

    public function updateDiscount(string $key, float $discount): void
    {
        if (!isset($this->cart[$key])) return;
        $this->cart[$key]['discount_percent'] = min(100, max(0, $discount));
        $this->recalcCartItem($key);
    }

    public function removeFromCart(string $key): void
    {
        unset($this->cart[$key]);
    }

    public function clearCart(): void
    {
        $this->cart = [];
    }

    private function recalcCartItem(string $key): void
    {
        $item     = $this->cart[$key];
        $gross    = round((float)$item['quantity'] * (float)$item['unit_price'], 2);
        $disc     = round($gross * ((float)$item['discount_percent'] / 100), 2);
        $net      = $gross - $disc;
        $sst      = $item['is_sst_applicable']
            ? round($net * ((float)$item['sst_rate'] / 100), 2)
            : 0;

        $this->cart[$key]['subtotal']     = $net;
        $this->cart[$key]['sst_amount']   = $sst;
        $this->cart[$key]['total_amount'] = $net + $sst;
    }

    // =========================================================================
    // TOTALS
    // =========================================================================
    #[Computed]
    public function cartSubtotal(): float
    {
        return round(collect($this->cart)->sum('subtotal'), 2);
    }

    #[Computed]
    public function cartTax(): float
    {
        return round(collect($this->cart)->sum('sst_amount'), 2);
    }

    #[Computed]
    public function cartTotal(): float
    {
        return round(collect($this->cart)->sum('total_amount'), 2);
    }

    #[Computed]
    public function changeAmount(): float
    {
        return max(0, round($this->amountTendered - $this->cartTotal, 2));
    }

    // =========================================================================
    // CHECKOUT
    // =========================================================================
    public function proceedCheckout(): void
    {
        if (empty($this->cart)) return;
        $this->amountTendered = $this->cartTotal;
        $this->showCheckout   = true;
    }

    public function processPayment(): void
    {
        if (empty($this->cart)) return;

        if ($this->paymentMethod === 'cash' && $this->amountTendered < $this->cartTotal) {
            session()->flash('error', 'Amaun tidak mencukupi!');
            return;
        }

        try {
            $transaction = app(PosService::class)->processSale(
                $this->session,
                array_values($this->cart),
                $this->paymentMethod,
                $this->amountTendered,
                $this->customerName ?: null,
                $this->notes ?: null
            );

            // Build receipt data
            $this->lastReceipt = [
                'transaction_no'   => $transaction->transaction_no,
                'items'            => array_values($this->cart),
                'subtotal'         => $this->cartSubtotal,
                'tax'              => $this->cartTax,
                'total'            => $this->cartTotal,
                'payment_method'   => $this->paymentMethod,
                'amount_tendered'  => $this->amountTendered,
                'change'           => $this->changeAmount,
                'customer'         => $this->customerName,
                'datetime'         => now()->format('d/m/Y H:i'),
            ];

            // Reset
            $this->cart           = [];
            $this->showCheckout   = false;
            $this->customerName   = '';
            $this->notes          = '';
            $this->amountTendered = 0;
            $this->showReceipt    = true;

        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function closeReceipt(): void
    {
        $this->showReceipt = false;
        $this->lastReceipt = null;
    }

    // =========================================================================
    // SEARCH
    // =========================================================================
    #[Computed]
    public function searchResults(): \Illuminate\Database\Eloquent\Collection
    {
        if (strlen($this->search) < 1) {
            return Item::where('is_active', true)
                ->orderBy('name')
                ->limit(20)
                ->get();
        }

        return Item::where('is_active', true)
            ->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('code', 'like', "%{$this->search}%");
            })
            ->limit(20)
            ->get();
    }

    public function render()
    {
        return view('livewire.pos-cashier')
            ->layout('layouts.pos');
    }
}
