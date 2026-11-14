@extends('layouts.app')
@section('title', $store->store_name)
@section('content')
<a class="back-link" href="{{ route('consumer.home') }}">← Kembali ke jelajah</a>
<section class="store-hero">
    <div class="store-hero-logo">
        @if ($store->logo_path)<img src="{{ Storage::disk('public')->url($store->logo_path) }}" alt="Logo {{ $store->store_name }}">
        @else<span>{{ mb_substr($store->store_name, 0, 1) }}</span>@endif
    </div>
    <div class="store-hero-copy"><span class="eyebrow"><span class="eyebrow-dot"></span> Toko lokal</span><h1>{{ $store->store_name }}</h1><p>{{ $store->description ?: 'Pilihan menu rumahan, dibuat dengan sepenuh hati.' }}</p><div class="store-meta"><span class="{{ $store->is_open ? 'status-open' : 'status-closed' }}"><i></i>{{ $store->is_open ? 'Sedang buka' : 'Sedang tutup' }}</span><span>⌖ {{ $store->address ?: 'Alamat belum tersedia' }}</span></div></div>
</section>
<div class="section-title-row"><div><h2>Menu hari ini</h2><p class="muted">{{ $store->is_open ? 'Pilih yang paling menggugah selera.' : 'Toko sedang tutup, menu belum dapat dipesan.' }}</p></div><a class="button button-outline button-small" href="{{ route('cart.show') }}">Lihat keranjang →</a></div>
@if ($store->products->isEmpty())
    <div class="empty-state"><span class="empty-icon">✿</span><h3>Belum ada menu yang tersedia.</h3><p>Menu yang tersedia akan muncul di halaman ini.</p></div>
@else
    <div class="menu-grid">
        @foreach ($store->products as $product)
            <article class="menu-card">
                <div class="menu-image">
                    @if ($product->image_path)<img src="{{ Storage::disk('public')->url($product->image_path) }}" alt="{{ $product->name }}">
                    @else<span class="menu-placeholder">✿</span>@endif
                    @if ($product->stock_qty <= 3)<span class="stock-hint">Sisa {{ $product->stock_qty }}</span>@endif
                </div>
                <div class="menu-content"><h3>{{ $product->name }}</h3><p>{{ $product->description ?: 'Disiapkan segar oleh dapur lokal.' }}</p>
                    <div class="menu-card-foot"><strong>{{ 'Rp'.number_format($product->price, 0, ',', '.') }}</strong>
                        @if ($store->is_open)
                            <form method="post" action="{{ route('cart.add', $product) }}" class="add-form">@csrf<input aria-label="Jumlah {{ $product->name }}" type="number" name="quantity" value="1" min="1" max="{{ min(99, $product->stock_qty) }}"><button class="add-button" aria-label="Tambah {{ $product->name }}" type="submit">+</button></form>
                        @else<span class="muted small">Toko tutup</span>@endif
                    </div>
                </div>
            </article>
        @endforeach
    </div>
@endif
@endsection
