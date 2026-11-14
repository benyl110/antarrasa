@extends('layouts.app')
@section('title', 'Keranjang')
@section('content')
<div class="page-heading"><span class="eyebrow"><span class="eyebrow-dot"></span> Sebelum jadi pesanan</span><h1>Keranjang <em>pilihanmu.</em></h1><p class="muted">Satu keranjang untuk satu toko. Ongkir delivery dihitung sekali per pesanan.</p></div>
@if (! $cart || ! $store || $products->isEmpty())
    <div class="empty-state"><span class="empty-icon">⌑</span><h3>Keranjangmu masih kosong.</h3><p>Jelajahi toko sekitar dan temukan menu yang kamu suka.</p><a class="button button-primary" href="{{ route('consumer.home') }}">Jelajahi toko →</a></div>
@else
    @php($subtotal = 0)
    <div class="checkout-grid">
        <section class="panel">
            <div class="panel-heading"><div><span class="eyebrow">Dari satu toko</span><h2>{{ $store->store_name }}</h2></div><a class="text-link" href="{{ route('consumer.store', $store) }}">Tambah menu</a></div>
            <div class="cart-items">
                @foreach ($cart['items'] as $id => $quantity)
                    @if ($products->has($id))
                        @php($product = $products->get($id))
                        @php($subtotal += $product->price * $quantity)
                        <div class="cart-item">
                            <div class="cart-item-thumb">@if ($product->image_path)<img src="{{ Storage::disk('public')->url($product->image_path) }}" alt="">@else<span>✿</span>@endif</div>
                            <div class="cart-item-main"><strong>{{ $product->name }}</strong><small>{{ 'Rp'.number_format($product->price, 0, ',', '.') }} / item</small>
                                <div class="cart-item-actions">
                                    <form method="post" action="{{ route('cart.update', $product) }}">@csrf @method('PUT')<input type="number" name="quantity" value="{{ $quantity }}" min="1" max="{{ min(99, $product->stock_qty) }}" aria-label="Jumlah {{ $product->name }}"><button class="button button-quiet button-small" type="submit">Perbarui</button></form>
                                    <form method="post" action="{{ route('cart.remove', $product) }}">@csrf @method('DELETE')<button class="text-button" type="submit">Hapus</button></form>
                                </div>
                            </div>
                            <strong class="cart-line-total">{{ 'Rp'.number_format($product->price * $quantity, 0, ',', '.') }}</strong>
                        </div>
                    @endif
                @endforeach
            </div>
        </section>
        <aside class="panel summary-panel"><span class="eyebrow">Ringkasan</span><h2>Rincian pesanan</h2><div class="summary-row"><span>Subtotal menu</span><strong>{{ 'Rp'.number_format($subtotal, 0, ',', '.') }}</strong></div><div class="summary-row"><span>Biaya pengantaran</span><span class="muted">Dihitung setelah verifikasi jarak</span></div><div class="summary-total"><span>Total sementara</span><strong>{{ 'Rp'.number_format($subtotal, 0, ',', '.') }}</strong></div><p class="summary-note">Tidak ada biaya tambahan berdasarkan jumlah variasi menu.</p><a class="button button-primary button-full" href="{{ route('checkout') }}">Lanjut ke checkout →</a></aside>
    </div>
@endif
@endsection
