<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Validation\ValidationException;

class OrderWorkflow
{
    public static function deliveryFee(float $distance): int
    {
        return $distance <= 2 ? 5000 : 8000;
    }

    public function reserveStock(Order $order): void
    {
        if ($order->stock_reserved) {
            return;
        }

        foreach ($order->items as $item) {
            $product = Product::query()
                ->where('store_id', $order->store_id)
                ->whereKey($item->product_id)
                ->lockForUpdate()
                ->first();

            if (! $product || $product->availability_status !== 'available') {
                throw ValidationException::withMessages([
                    'order' => "Menu {$item->product_name} sudah tidak tersedia.",
                ]);
            }

            if ($product->price !== $item->unit_price) {
                throw ValidationException::withMessages([
                    'order' => "Harga {$item->product_name} berubah. Silakan buat pesanan baru.",
                ]);
            }

            if ($product->stock_qty < $item->quantity) {
                throw ValidationException::withMessages([
                    'order' => "Stok {$item->product_name} tidak mencukupi.",
                ]);
            }

            $product->decrement('stock_qty', $item->quantity);
        }

        $order->update(['stock_reserved' => true]);
    }

    public function releaseStock(Order $order): void
    {
        if (! $order->stock_reserved) {
            return;
        }

        foreach ($order->items as $item) {
            if (! $item->product_id) {
                continue;
            }

            $product = Product::query()->whereKey($item->product_id)->lockForUpdate()->first();
            $product?->increment('stock_qty', $item->quantity);
        }

        $order->update(['stock_reserved' => false]);
    }
}
