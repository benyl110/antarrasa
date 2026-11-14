@extends('layouts.app')
@section('title', $history ? 'Riwayat pesanan' : 'Pesanan toko')
@section('content')
<div class="page-heading heading-with-aside"><div><span class="eyebrow"><span class="eyebrow-dot"></span> {{ $history ? 'Catatan transaksi' : 'Ruang operasional' }}</span><h1>{{ $history ? 'Riwayat' : 'Pesanan' }} <em>toko.</em></h1><p class="muted">Kelola setiap pesanan dengan informasi yang lengkap.</p></div><div class="segmented"><a href="{{ route('partner.orders') }}" class="{{ ! $history ? 'selected' : '' }}">Aktif</a><a href="{{ route('partner.orders', ['history' => 1]) }}" class="{{ $history ? 'selected' : '' }}">Riwayat</a></div></div>
@if ($orders->isEmpty())
    <div class="empty-state"><span class="empty-icon">⌑</span><h3>{{ $history ? 'Belum ada riwayat pesanan.' : 'Belum ada pesanan aktif.' }}</h3><p>{{ $history ? 'Pesanan yang selesai atau dibatalkan akan tampil di sini.' : 'Permintaan pesanan baru dari Konsumen akan tampil di sini.' }}</p></div>
@else
    <div class="order-list">
        @foreach ($orders as $order)
            <a class="order-card" href="{{ route('partner.orders.show', $order) }}"><div class="order-card-main"><span class="order-code">{{ $order->order_code }} · {{ $order->consumer->name }}</span><h3>{{ $order->delivery_method === 'delivery' ? 'Delivery · '.$order->recipient_name : 'Pickup' }}</h3><p>{{ $order->created_at->format('d M Y · H:i') }} · {{ $order->items()->count() }} menu</p></div><div class="order-card-end"><span class="status-badge status-{{ $order->order_status }}">{{ str($order->order_status)->replace('_', ' ')->title() }}</span><strong>{{ 'Rp'.number_format($order->total, 0, ',', '.') }}</strong><span class="card-arrow">↗</span></div></a>
        @endforeach
    </div>
    <div class="pagination-wrap">{{ $orders->links() }}</div>
@endif
@endsection
