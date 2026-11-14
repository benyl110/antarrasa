@extends('layouts.app')
@section('title', 'Ruang Mitra')
@section('content')
<section class="page-heading partner-heading">
    <div><span class="eyebrow"><span class="eyebrow-dot"></span> Ruang Mitra</span><h1>Halo, {{ auth()->user()->name }} <em>♡</em></h1><p class="muted">Semoga hari ini banyak cerita baik dari {{ $store->store_name }}.</p></div>
    <a class="button {{ $store->is_open ? 'button-outline' : 'button-primary' }}" href="{{ route('partner.store.edit') }}"><span class="store-toggle-dot"></span>{{ $store->is_open ? 'Toko sedang buka' : 'Toko sedang tutup' }} · Ubah</a>
</section>
@if ($store->store_status !== 'active')
    <div class="flash flash-warn"><span>!</span> Pengajuan toko menunggu aktivasi. Kamu dapat menyiapkan profil dan menu, tetapi belum dapat menerima pesanan.</div>
@endif
<section class="stats-grid">
    <article class="stat-card stat-highlight"><span class="stat-icon">⌑</span><span class="stat-label">Perlu ditangani</span><strong>{{ $stats['new'] }}</strong><small>Permintaan pesanan baru</small></article>
    <article class="stat-card"><span class="stat-icon stat-icon-green">◷</span><span class="stat-label">Sedang diproses</span><strong>{{ $stats['active'] }}</strong><small>Pesanan aktif</small></article>
    <article class="stat-card"><span class="stat-icon stat-icon-peach">✿</span><span class="stat-label">Menu tersedia</span><strong>{{ $stats['products'] }}</strong><small>Menu di katalog toko</small></article>
    <article class="stat-card"><span class="stat-icon stat-icon-blue">✓</span><span class="stat-label">Pesanan selesai</span><strong>{{ $stats['completed'] }}</strong><small>Sepanjang waktu</small></article>
</section>
<div class="section-title-row"><div><h2>Pesanan terbaru</h2><p class="muted">Respons cepat membantu pelanggan merasa tenang.</p></div><a class="text-link" href="{{ route('partner.orders') }}">Semua pesanan →</a></div>
@if ($orders->isEmpty())
    <div class="empty-state compact-empty"><span class="empty-icon">⌑</span><h3>Belum ada pesanan masuk.</h3><p>Pesanan baru dari pelanggan akan tampil di sini.</p><a class="button button-outline" href="{{ route('partner.products') }}">Kelola menu</a></div>
@else
    <div class="order-list">
        @foreach ($orders as $order)
            <a class="order-card" href="{{ route('partner.orders.show', $order) }}"><div class="order-card-main"><span class="order-code">{{ $order->order_code }} · {{ $order->consumer->name }}</span><h3>{{ $order->delivery_method === 'delivery' ? 'Diantar' : 'Ambil sendiri' }}</h3><p>{{ $order->created_at->format('d M Y · H:i') }}</p></div><div class="order-card-end"><span class="status-badge status-{{ $order->order_status }}">{{ str($order->order_status)->replace('_', ' ')->title() }}</span><strong>{{ 'Rp'.number_format($order->total, 0, ',', '.') }}</strong><span class="card-arrow">↗</span></div></a>
        @endforeach
    </div>
@endif
<section class="quick-actions"><a href="{{ route('partner.products.create') }}"><span>＋</span><div><strong>Tambah menu</strong><small>Perbarui pilihan pelanggan</small></div><b>↗</b></a><a href="{{ route('partner.store.edit') }}"><span>✿</span><div><strong>Profil toko</strong><small>Jam buka, kontak, dan alamat</small></div><b>↗</b></a></section>
@endsection
