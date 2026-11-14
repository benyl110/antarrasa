<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Services\OrderWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PartnerController extends Controller
{
    private function store(Request $request): Store
    {
        return $request->user()->store()->firstOrFail();
    }

    private function ownedOrder(Request $request, Order $order): Order
    {
        abort_unless($order->store_id === $this->store($request)->id, 404);

        return $order;
    }

    private function ownedProduct(Request $request, Product $product): Product
    {
        abort_unless($product->store_id === $this->store($request)->id, 404);

        return $product;
    }

    public function dashboard(Request $request): View
    {
        $store = $this->store($request);
        $orders = $store->orders()->with('consumer')->latest()->limit(6)->get();
        $stats = [
            'new' => $store->orders()->whereIn('order_status', ['pending', 'awaiting_distance'])->count(),
            'active' => $store->orders()->whereIn('order_status', ['accepted', 'preparing', 'ready_for_pickup', 'on_delivery'])->count(),
            'products' => $store->products()->where('availability_status', 'available')->count(),
            'completed' => $store->orders()->where('order_status', 'completed')->count(),
        ];

        return view('partner.dashboard', compact('store', 'orders', 'stats'));
    }

    public function editStore(Request $request): View
    {
        $store = $this->store($request);

        return view('partner.store', compact('store'));
    }

    public function updateStore(Request $request): RedirectResponse
    {
        $store = $this->store($request);
        $data = $request->validate([
            'store_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'phone' => ['required', 'string', 'regex:/^[0-9+()\\-\\s]{8,30}$/'],
            'address' => ['required', 'string', 'max:2000'],
            'bank_name' => ['nullable', 'required_with:bank_account_name,bank_account_number', 'string', 'max:100'],
            'bank_account_name' => ['nullable', 'required_with:bank_name,bank_account_number', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'required_with:bank_name,bank_account_name', 'string', 'max:100'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'is_open' => ['sometimes', 'boolean'],
        ]);

        $data['is_open'] = $store->store_status === 'active' && $request->boolean('is_open');
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('stores', 'public');
            if (! is_string($path)) {
                throw new \RuntimeException('Logo toko gagal disimpan.');
            }
            if ($store->logo_path) {
                Storage::disk('public')->delete($store->logo_path);
            }
            $data['logo_path'] = $path;
        }

        $store->update($data);
        $request->user()->update(['phone' => $data['phone']]);

        return back()->with('status', 'Profil toko berhasil diperbarui.');
    }

    public function products(Request $request): View
    {
        $products = $this->store($request)->products()->orderByDesc('created_at')->paginate(12);

        return view('partner.products', compact('products'));
    }

    public function createProduct(Request $request): View
    {
        $product = new Product;

        return view('partner.product-form', compact('product'));
    }

    public function storeProduct(Request $request): RedirectResponse
    {
        $store = $this->store($request);
        $data = $this->validatedProduct($request);
        if ($request->hasFile('image')) {
            $data['image_path'] = $this->storeProductImage($request);
        }
        $store->products()->create($data);

        return redirect()->route('partner.products')->with('status', 'Menu berhasil ditambahkan.');
    }

    public function editProduct(Request $request, Product $product): View
    {
        $product = $this->ownedProduct($request, $product);

        return view('partner.product-form', compact('product'));
    }

    public function updateProduct(Request $request, Product $product): RedirectResponse
    {
        $product = $this->ownedProduct($request, $product);
        $data = $this->validatedProduct($request);
        if ($request->hasFile('image')) {
            $data['image_path'] = $this->storeProductImage($request);
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
        }
        $product->update($data);

        return redirect()->route('partner.products')->with('status', 'Menu berhasil diperbarui.');
    }

    public function archiveProduct(Request $request, Product $product): RedirectResponse
    {
        $product = $this->ownedProduct($request, $product);
        $product->update(['availability_status' => 'unavailable']);

        return back()->with('status', 'Menu diarsipkan dan tidak ditampilkan kepada konsumen.');
    }

    private function validatedProduct(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'price' => ['required', 'integer', 'min:1', 'max:100000000'],
            'stock_qty' => ['required', 'integer', 'min:0', 'max:1000000'],
            'availability_status' => ['required', 'in:available,unavailable'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ]);
    }

    private function storeProductImage(Request $request): string
    {
        $path = $request->file('image')->store('products', 'public');
        if (! is_string($path)) {
            throw new \RuntimeException('Foto menu gagal disimpan.');
        }

        return $path;
    }

    public function orders(Request $request): View
    {
        $history = $request->boolean('history');
        $orders = $this->store($request)->orders()->with('consumer')
            ->when(
                $history,
                fn ($query) => $query->whereIn('order_status', ['completed', 'rejected', 'cancelled']),
                fn ($query) => $query->whereNotIn('order_status', ['completed', 'rejected', 'cancelled'])
            )
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('partner.orders', compact('orders', 'history'));
    }

    public function showOrder(Request $request, Order $order): View
    {
        $order = $this->ownedOrder($request, $order)->load(['consumer', 'items', 'store']);

        return view('partner.order', compact('order'));
    }

    public function verifyDistance(Request $request, Order $order): RedirectResponse
    {
        $order = $this->ownedOrder($request, $order);
        abort_unless($order->order_status === 'awaiting_distance' && $order->delivery_method === 'delivery', 422);
        $data = $request->validate(['distance_km' => ['required', 'numeric', 'gt:0', 'max:100']]);
        $routeDistance = (float) $data['distance_km'];
        $distance = round($routeDistance, 2);

        $withinRange = DB::transaction(function () use ($order, $request, $routeDistance, $distance): bool {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->order_status === 'awaiting_distance' && $locked->delivery_method === 'delivery', 422);

            if ($routeDistance > 5) {
                $locked->update([
                    'delivery_distance_km' => $distance,
                    'distance_verified_by' => $request->user()->id,
                    'distance_verified_at' => now(),
                    'order_status' => 'rejected',
                    'cancellation_reason' => 'Jarak pengantaran melebihi batas maksimum 5 km.',
                ]);

                return false;
            }

            $fee = OrderWorkflow::deliveryFee($routeDistance);
            $locked->update([
                'delivery_distance_km' => $distance,
                'distance_verified_by' => $request->user()->id,
                'distance_verified_at' => now(),
                'delivery_fee' => $fee,
                'total' => $locked->subtotal + $fee,
                'order_status' => 'awaiting_customer_confirmation',
            ]);

            return true;
        });

        if (! $withinRange) {
            return back()->with('status', 'Permintaan ditolak karena jarak melebihi 5 km. Konsumen dapat memilih pickup pada pesanan baru.');
        }

        return back()->with('status', 'Jarak dan ongkir dikirim ke Konsumen untuk persetujuan.');
    }

    public function decideOrder(Request $request, Order $order, OrderWorkflow $workflow): RedirectResponse
    {
        $order = $this->ownedOrder($request, $order);
        $data = $request->validate(['decision' => ['required', 'in:accept,reject']]);
        abort_unless($order->order_status === 'pending', 422);

        DB::transaction(function () use ($order, $data, $workflow): void {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->order_status === 'pending', 422);

            if ($data['decision'] === 'reject') {
                $workflow->releaseStock($locked->load('items'));
                $locked->update(['order_status' => 'rejected']);
            } else {
                $locked->update(['order_status' => 'accepted']);
            }
        });

        return back()->with('status', $data['decision'] === 'accept' ? 'Pesanan diterima.' : 'Pesanan ditolak dan stok dikembalikan.');
    }

    public function advanceOrder(Request $request, Order $order): RedirectResponse
    {
        $order = $this->ownedOrder($request, $order);
        $data = $request->validate(['order_status' => ['required', 'in:preparing,ready_for_pickup,on_delivery,completed']]);
        DB::transaction(function () use ($order, $data): void {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $allowed = [
                'accepted' => ['preparing'],
                'preparing' => ['ready_for_pickup'],
                'ready_for_pickup' => $locked->delivery_method === 'delivery' ? ['on_delivery'] : ['completed'],
                'on_delivery' => ['completed'],
            ];

            abort_unless(in_array($data['order_status'], $allowed[$locked->order_status] ?? [], true), 422, 'Perubahan status tidak diizinkan.');
            $locked->update(['order_status' => $data['order_status']]);
        });

        return back()->with('status', 'Status pesanan diperbarui.');
    }

    public function decideCancellation(Request $request, Order $order, OrderWorkflow $workflow): RedirectResponse
    {
        $order = $this->ownedOrder($request, $order);
        $data = $request->validate(['decision' => ['required', 'in:approve,reject']]);
        abort_unless($order->order_status === 'cancellation_requested', 422);

        DB::transaction(function () use ($order, $data, $workflow): void {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->order_status === 'cancellation_requested', 422);

            $approved = $data['decision'] === 'approve';
            $changes = [
                'order_status' => $approved ? 'cancelled' : ($locked->cancellation_previous_status ?: 'accepted'),
                'cancellation_decision' => $approved ? 'approved' : 'rejected',
                'cancellation_decided_at' => now(),
            ];
            if ($approved) {
                $workflow->releaseStock($locked->load('items'));
                if ($locked->payment_status === 'paid') {
                    $changes['payment_status'] = 'refund_pending';
                }
            }
            $locked->update($changes);
        });

        return back()->with('status', $data['decision'] === 'approve'
            ? 'Pembatalan disetujui; stok dikembalikan bila sebelumnya dicadangkan.'
            : 'Permintaan pembatalan ditolak.');
    }

    public function updateDriver(Request $request, Order $order): RedirectResponse
    {
        $order = $this->ownedOrder($request, $order);
        abort_unless($order->delivery_method === 'delivery' && in_array($order->order_status, ['accepted', 'preparing', 'ready_for_pickup', 'on_delivery'], true), 422);
        $data = $request->validate([
            'driver_name' => ['nullable', 'string', 'max:255'],
            'driver_phone' => ['nullable', 'string', 'max:30'],
            'driver_cost' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'driver_booking_status' => ['required', 'in:not_booked,contacting,booked,failed'],
        ]);
        $order->update($data);

        return back()->with('status', 'Informasi pengantaran diperbarui. Ongkir Konsumen tidak berubah.');
    }

    public function verifyPayment(Request $request, Order $order): RedirectResponse
    {
        $order = $this->ownedOrder($request, $order);
        abort_unless($order->payment_method === 'transfer' && $order->payment_status === 'awaiting_verification', 422);
        $data = $request->validate([
            'decision' => ['required', 'in:paid,rejected'],
            'payment_rejection_reason' => ['required_if:decision,rejected', 'nullable', 'string', 'max:2000'],
        ]);
        $order->update([
            'payment_status' => $data['decision'],
            'payment_rejection_reason' => $data['decision'] === 'rejected' ? $data['payment_rejection_reason'] : null,
            'payment_verified_at' => now(),
        ]);

        return back()->with('status', $data['decision'] === 'paid' ? 'Pembayaran ditandai diterima.' : 'Bukti ditolak; Konsumen dapat mengunggah ulang.');
    }

    public function confirmCodPayment(Request $request, Order $order): RedirectResponse
    {
        $order = $this->ownedOrder($request, $order);
        abort_unless($order->payment_method === 'cod' && $order->payment_status === 'unpaid' && $order->order_status === 'completed', 422);
        $order->update(['payment_status' => 'paid', 'payment_verified_at' => now()]);

        return back()->with('status', 'Pembayaran tunai dicatat sudah diterima.');
    }

    public function confirmRefund(Request $request, Order $order): RedirectResponse
    {
        $order = $this->ownedOrder($request, $order);
        abort_unless($order->payment_status === 'refund_pending' && $order->order_status === 'cancelled', 422);
        $data = $request->validate(['refund_note' => ['required', 'string', 'max:2000']]);
        $order->update(['payment_status' => 'refunded', 'refund_note' => $data['refund_note']]);

        return back()->with('status', 'Pengembalian dana manual dicatat.');
    }
}
