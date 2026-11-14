<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Store;
use App\Services\OrderWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StorefrontController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $stores = Store::query()
            ->withCount(['products' => fn ($query) => $query->where('availability_status', 'available')->where('stock_qty', '>', 0)])
            ->where('store_status', 'active')
            ->where('is_open', true)
            ->when($search, fn ($query) => $query->where('store_name', 'like', "%{$search}%"))
            ->orderBy('store_name')
            ->paginate(12)
            ->withQueryString();

        return view('consumer.home', compact('stores', 'search'));
    }

    public function showStore(Store $store): View
    {
        abort_unless($store->store_status === 'active', 404);

        $store->load(['products' => fn ($query) => $query->where('availability_status', 'available')->where('stock_qty', '>', 0)->orderBy('name')]);

        return view('consumer.store', compact('store'));
    }

    public function addToCart(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:99']]);
        $product->load('store');

        abort_unless(
            $product->availability_status === 'available'
                && $product->stock_qty >= $data['quantity']
                && $product->store->store_status === 'active'
                && $product->store->is_open,
            422,
            'Menu tidak tersedia atau toko sedang tutup.'
        );

        $cart = $request->session()->get('cart');
        if ($cart && (int) $cart['store_id'] !== $product->store_id) {
            return back()->withErrors(['cart' => 'Keranjang hanya dapat berisi menu dari satu toko. Kosongkan keranjang untuk memilih toko lain.']);
        }

        $cart ??= ['store_id' => $product->store_id, 'items' => []];
        $current = (int) ($cart['items'][$product->id] ?? 0);
        if ($current + $data['quantity'] > $product->stock_qty) {
            return back()->withErrors(['cart' => 'Jumlah yang dipilih melebihi stok tersedia.']);
        }

        $cart['items'][$product->id] = $current + $data['quantity'];
        $request->session()->put('cart', $cart);

        return redirect()->route('cart.show')->with('status', 'Menu ditambahkan ke keranjang.');
    }

    public function showCart(Request $request): View
    {
        $cart = $request->session()->get('cart');
        $store = $cart ? Store::with('products')->find($cart['store_id']) : null;
        $products = $store
            ? Product::where('store_id', $store->id)->whereIn('id', array_keys($cart['items']))->get()->keyBy('id')
            : collect();

        return view('consumer.cart', compact('cart', 'store', 'products'));
    }

    public function updateCart(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:99']]);
        $cart = $request->session()->get('cart');
        abort_unless($cart && (int) $cart['store_id'] === $product->store_id, 404);

        if ($product->availability_status !== 'available' || $data['quantity'] > $product->stock_qty) {
            return back()->withErrors(['cart' => 'Jumlah melebihi stok yang tersedia.']);
        }

        $cart['items'][$product->id] = $data['quantity'];
        $request->session()->put('cart', $cart);

        return back()->with('status', 'Keranjang diperbarui.');
    }

    public function removeFromCart(Request $request, Product $product): RedirectResponse
    {
        $cart = $request->session()->get('cart');
        if ($cart && (int) $cart['store_id'] === $product->store_id) {
            unset($cart['items'][$product->id]);
            if ($cart['items'] === []) {
                $request->session()->forget('cart');
            } else {
                $request->session()->put('cart', $cart);
            }
        }

        return back()->with('status', 'Menu dihapus dari keranjang.');
    }

    public function checkoutForm(Request $request): View|RedirectResponse
    {
        $cart = $request->session()->get('cart');
        if (! $cart || $cart['items'] === []) {
            return redirect()->route('cart.show')->withErrors(['cart' => 'Keranjang masih kosong.']);
        }

        $store = Store::findOrFail($cart['store_id']);
        $products = Product::whereIn('id', array_keys($cart['items']))->get()->keyBy('id');
        $checkoutToken = $request->session()->get('checkout_token');
        if (! is_string($checkoutToken) || strlen($checkoutToken) !== 64) {
            $checkoutToken = Str::random(64);
            $request->session()->put('checkout_token', $checkoutToken);
        }

        return view('consumer.checkout', compact('cart', 'store', 'products', 'checkoutToken'));
    }

    public function checkout(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'delivery_method' => ['required', 'in:delivery,pickup'],
            'payment_method' => ['required', 'in:transfer,cod'],
            'checkout_token' => ['required', 'regex:/^[A-Za-z0-9]{64}$/'],
            'recipient_name' => ['required_if:delivery_method,delivery', 'nullable', 'string', 'max:255'],
            'recipient_phone' => ['required_if:delivery_method,delivery', 'nullable', 'string', 'regex:/^[0-9+()\\-\\s]{8,30}$/'],
            'delivery_address' => ['required_if:delivery_method,delivery', 'nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $existingOrder = Order::query()
            ->where('checkout_token', $data['checkout_token'])
            ->where('consumer_id', $request->user()->id)
            ->first();

        if ($existingOrder) {
            return redirect()->route('orders.show', $existingOrder)
                ->with('status', 'Permintaan checkout ini sudah pernah diproses.');
        }

        $cart = $request->session()->get('cart');
        if (! $cart || $cart['items'] === []) {
            return redirect()->route('cart.show')->withErrors(['cart' => 'Keranjang masih kosong.']);
        }

        if (! hash_equals((string) $request->session()->get('checkout_token'), $data['checkout_token'])) {
            throw ValidationException::withMessages(['checkout_token' => 'Sesi checkout kedaluwarsa. Muat ulang halaman checkout.']);
        }

        $order = DB::transaction(function () use ($request, $data, $cart): Order {
            $store = Store::query()->whereKey($cart['store_id'])->lockForUpdate()->firstOrFail();
            $existingOrder = Order::query()
                ->where('checkout_token', $data['checkout_token'])
                ->where('consumer_id', $request->user()->id)
                ->first();

            if ($existingOrder) {
                return $existingOrder;
            }

            if ($store->store_status !== 'active' || ! $store->is_open) {
                throw ValidationException::withMessages(['order' => 'Toko sedang tutup dan tidak menerima pesanan.']);
            }
            if ($data['payment_method'] === 'transfer'
                && (! $store->bank_name || ! $store->bank_account_name || ! $store->bank_account_number)) {
                throw ValidationException::withMessages(['payment_method' => 'Toko belum mengatur rekening transfer. Pilih pembayaran tunai (COD).']);
            }

            $products = Product::query()
                ->where('store_id', $store->id)
                ->whereIn('id', array_keys($cart['items']))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== count($cart['items'])) {
                throw ValidationException::withMessages(['order' => 'Salah satu menu di keranjang sudah tidak tersedia.']);
            }

            $subtotal = 0;
            foreach ($cart['items'] as $productId => $quantity) {
                $product = $products->get($productId);
                if ($product->availability_status !== 'available' || $product->stock_qty < $quantity) {
                    throw ValidationException::withMessages(['order' => "Stok {$product->name} tidak mencukupi."]);
                }
                $subtotal += $product->price * $quantity;
            }

            $delivery = $data['delivery_method'] === 'delivery';
            $order = Order::create([
                'order_code' => 'AR'.now()->format('ymd').Str::upper(Str::random(6)),
                'checkout_token' => $data['checkout_token'],
                'consumer_id' => $request->user()->id,
                'store_id' => $store->id,
                'order_status' => $delivery ? 'awaiting_distance' : 'pending',
                'delivery_method' => $data['delivery_method'],
                'recipient_name' => $data['recipient_name'] ?? $request->user()->name,
                'recipient_phone' => $data['recipient_phone'] ?? $request->user()->phone,
                'delivery_address' => $data['delivery_address'] ?? null,
                'payment_method' => $data['payment_method'],
                'payment_status' => 'unpaid',
                'subtotal' => $subtotal,
                'delivery_fee' => 0,
                'total' => $subtotal,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($cart['items'] as $productId => $quantity) {
                $product = $products->get($productId);
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit_price' => $product->price,
                    'quantity' => $quantity,
                    'line_total' => $product->price * $quantity,
                ]);
            }

            if (! $delivery) {
                app(OrderWorkflow::class)->reserveStock($order->load('items'));
            }

            return $order;
        });

        $request->session()->forget('cart');
        $request->session()->put('checkout_token', Str::random(64));

        return redirect()->route('orders.show', $order)->with('status', $order->delivery_method === 'delivery'
            ? 'Permintaan pesanan terkirim. Toko akan memverifikasi jarak dan ongkir.'
            : 'Pesanan pickup berhasil dibuat dan menunggu konfirmasi toko.');
    }
}
