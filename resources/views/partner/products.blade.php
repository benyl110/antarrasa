@extends('layouts.app')
@section('title', 'Kelola menu')
@section('content')
<div class="page-heading heading-with-aside"><div><span class="eyebrow"><span class="eyebrow-dot"></span> Katalog toko</span><h1>Menu <em>andalanmu.</em></h1><p class="muted">Atur nama, harga, stok, dan ketersediaan produk.</p></div><a class="button button-primary" href="{{ route('partner.products.create') }}">＋ Tambah menu</a></div>
@if ($products->isEmpty())
    <div class="empty-state"><span class="empty-icon">✿</span><h3>Belum ada menu di katalog.</h3><p>Tambahkan menu agar pelanggan dapat mulai memesan.</p><a class="button button-primary" href="{{ route('partner.products.create') }}">Tambah menu pertama →</a></div>
@else
    <div class="product-list">
        @foreach ($products as $product)
            <article class="product-row">
                <div class="product-row-image">@if ($product->image_path)<img src="{{ Storage::disk('public')->url($product->image_path) }}" alt="">@else<span>✿</span>@endif</div>
                <div class="product-row-copy"><div class="product-title"><h3>{{ $product->name }}</h3><span class="status-badge {{ $product->availability_status === 'available' ? 'status-completed' : 'status-cancelled' }}">{{ $product->availability_status === 'available' ? 'Tersedia' : 'Diarsipkan' }}</span></div><p>{{ $product->description ?: 'Belum ada deskripsi.' }}</p><small>Stok {{ $product->stock_qty }}</small></div>
                <strong class="product-price">{{ 'Rp'.number_format($product->price, 0, ',', '.') }}</strong>
                <div class="product-row-actions"><a class="button button-outline button-small" href="{{ route('partner.products.edit', $product) }}">Ubah</a>@if ($product->availability_status === 'available')<form method="post" action="{{ route('partner.products.archive', $product) }}" data-confirm="Arsipkan menu ini? Menu tidak akan tampil kepada pelanggan.">@csrf @method('DELETE')<button class="button button-quiet button-small" type="submit">Arsipkan</button></form>@endif</div>
            </article>
        @endforeach
    </div>
    <div class="pagination-wrap">{{ $products->links() }}</div>
@endif
@endsection
