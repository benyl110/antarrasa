@extends('layouts.app')
@section('title', 'Pesanan saya')
@section('content')
<div class="page-heading"><span class="eyebrow"><span class="eyebrow-dot"></span> Catatan rasa</span><h1>Pesanan <em>milikmu.</em></h1><p class="muted">Lihat perkembangan dan riwayat transaksimu.</p></div>
@if ($orders->isEmpty())
    <div class="empty-state"><span class="empty-icon">⌑</span><h3>Belum ada pesanan.</h3><p>Pesananmu akan tersimpan di sini setelah checkout.</p><a class="button button-primary" href="{{ route('consumer.home') }}">Cari makanan →</a></div>
@else
    <div class="order-list">
        @foreach ($orders as $order)
            <a class="order-card" href="{{ route('orders.show', $order) }}">
                <div class="order-card-main"><span class="order-code">{{ $order->order_code }}</span><h3>{{ $order->store->store_name }}</h3><p>{{ $order->created_at->format('d M Y · H:i') }} · {{ $order->items()->count() }} menu · {{ $order->delivery_method === 'delivery' ? 'Diantar' : 'Ambil sendiri' }}</p></div>
                <div class="order-card-end"><span class="status-badge status-{{ $order->order_status }}">{{ str($order->order_status)->replace('_', ' ')->title() }}</span><strong>{{ 'Rp'.number_format($order->total, 0, ',', '.') }}</strong><span class="card-arrow">↗</span></div>
            </a>
        @endforeach
    </div>
    <div class="pagination-wrap">{{ $orders->links() }}</div>
@endif
@endsection
