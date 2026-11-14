@extends('layouts.app')
@section('title', 'Pesanan '.$order->order_code)
@section('content')
<a class="back-link" href="{{ route('orders.index') }}">← Kembali ke pesanan</a>
@php
    $labels = [
        'awaiting_distance' => 'Menunggu verifikasi jarak',
        'awaiting_customer_confirmation' => 'Menunggu persetujuanmu',
        'pending' => 'Menunggu konfirmasi toko',
        'accepted' => 'Pesanan diterima',
        'preparing' => 'Sedang disiapkan',
        'ready_for_pickup' => 'Siap diambil / diantar',
        'on_delivery' => 'Sedang diantar',
        'completed' => 'Selesai',
        'rejected' => 'Pesanan ditolak',
        'cancellation_requested' => 'Menunggu keputusan pembatalan',
        'cancelled' => 'Dibatalkan',
    ];
@endphp
<div class="detail-heading"><div><span class="eyebrow"><span class="eyebrow-dot"></span> Pesanan {{ $order->order_code }}</span><h1>{{ $order->store->store_name }}</h1><p class="muted">Dibuat {{ $order->created_at->format('d M Y, H:i') }}</p></div><span class="status-badge status-{{ $order->order_status }}">{{ $labels[$order->order_status] ?? str($order->order_status)->replace('_', ' ')->title() }}</span></div>
<div class="checkout-grid order-detail-grid">
    <div class="panel-stack">
        <section class="panel">
            <div class="panel-heading"><div><span class="eyebrow">Rincian menu</span><h2>Yang kamu pesan</h2></div><span class="method-pill">{{ $order->delivery_method === 'delivery' ? '⌖ Diantar' : '↗ Ambil sendiri' }}</span></div>
            @foreach ($order->items as $item)
                <div class="summary-row item-summary"><span>{{ $item->product_name }} <small>× {{ $item->quantity }}</small></span><strong>{{ 'Rp'.number_format($item->line_total, 0, ',', '.') }}</strong></div>
            @endforeach
            <div class="summary-row"><span>Subtotal menu</span><strong>{{ 'Rp'.number_format($order->subtotal, 0, ',', '.') }}</strong></div>
            <div class="summary-row"><span>Ongkir{{ $order->delivery_distance_km ? ' · '.$order->delivery_distance_km.' km' : '' }}</span><strong>{{ 'Rp'.number_format($order->delivery_fee, 0, ',', '.') }}</strong></div>
            <div class="summary-total"><span>Total pesanan</span><strong>{{ 'Rp'.number_format($order->total, 0, ',', '.') }}</strong></div>
            <div class="detail-notes"><span>Metode bayar</span><strong>{{ $order->payment_method === 'transfer' ? 'Transfer manual' : 'Tunai (COD)' }} · {{ str($order->payment_status)->replace('_', ' ')->title() }}</strong></div>
            @if ($order->payment_method === 'transfer' && $order->store->bank_name && $order->store->bank_account_name && $order->store->bank_account_number && ! in_array($order->payment_status, ['paid', 'refunded'], true))
                <div class="info-panel"><strong>Tujuan transfer · {{ $order->store->bank_name }}</strong><p>{{ $order->store->bank_account_name }}<br><b>{{ $order->store->bank_account_number }}</b></p></div>
            @endif
            @if ($order->recipient_name)<div class="detail-notes"><span>Penerima dan alamat</span><strong>{{ $order->recipient_name }} · {{ $order->recipient_phone }}<br>{{ $order->delivery_address }}</strong></div>@endif
            @if ($order->delivery_method === 'delivery' && $order->driver_booking_status !== 'not_booked')
                <div class="detail-notes"><span>Pengantaran</span><strong>{{ str($order->driver_booking_status)->replace('_', ' ')->title() }}@if ($order->driver_name)<br>{{ $order->driver_name }}@endif @if ($order->driver_phone)<br><a href="tel:{{ $order->driver_phone }}">{{ $order->driver_phone }}</a>@endif</strong></div>
            @endif
            @if ($order->notes)<div class="detail-notes"><span>Catatan</span><strong>{{ $order->notes }}</strong></div>@endif
        </section>
        @if ($order->order_status === 'awaiting_customer_confirmation')
            <section class="panel attention-panel"><span class="eyebrow">Mohon konfirmasi</span><h2>Jarak & ongkir sudah diverifikasi</h2><p>Toko memverifikasi jarak rute <strong>{{ number_format($order->delivery_distance_km, 1, ',', '.') }} km</strong>. Ongkir satu kali sebesar <strong>{{ 'Rp'.number_format($order->delivery_fee, 0, ',', '.') }}</strong>, total akhir <strong>{{ 'Rp'.number_format($order->total, 0, ',', '.') }}</strong>.</p><form method="post" action="{{ route('orders.confirm-distance', $order) }}">@csrf<button class="button button-primary" type="submit">Setujui dan aktifkan pesanan →</button></form></section>
        @endif
        @if ($order->payment_method === 'transfer' && ! in_array($order->payment_status, ['paid', 'refund_pending', 'refunded'], true) && ! in_array($order->order_status, ['rejected', 'cancelled'], true))
            <section class="panel">
                <span class="eyebrow">Pembayaran transfer</span><h2>Bukti pembayaran</h2>
                <p class="muted">{{ $order->payment_status === 'awaiting_verification' ? 'Bukti sedang diperiksa oleh Mitra.' : ($order->payment_status === 'rejected' ? 'Bukti sebelumnya ditolak. Silakan unggah ulang.' : 'Unggah bukti transfer agar pembayaran dapat diverifikasi.') }}</p>
                @if ($order->payment_rejection_reason)<div class="flash flash-error">{{ $order->payment_rejection_reason }}</div>@endif
                @if ($order->payment_status !== 'awaiting_verification')
                    <form method="post" action="{{ route('orders.payment-proof', $order) }}" enctype="multipart/form-data" class="form-stack">
                        @csrf<label>File bukti (JPG, PNG, atau PDF; maks. 5 MB)<input type="file" name="payment_proof" accept=".jpg,.jpeg,.png,.pdf" required></label>
                        <button class="button button-outline" type="submit">Kirim bukti transfer</button>
                    </form>
                @endif
            </section>
        @endif
        @if ($order->payment_status === 'refund_pending')
            <section class="panel"><span class="eyebrow">Pengembalian manual</span><h2>Refund sedang diproses Mitra</h2><p class="muted">Hubungi toko untuk tindak lanjut pengembalian dana. {{ $order->refund_note }}</p></section>
        @endif
        @if ($order->order_status === 'rejected' && $order->cancellation_reason)<section class="panel"><span class="eyebrow">Informasi toko</span><p>{{ $order->cancellation_reason }}</p></section>@endif
        @if (in_array($order->order_status, ['awaiting_distance', 'awaiting_customer_confirmation', 'pending', 'accepted'], true))
            <section class="panel cancel-panel"><h2>{{ $order->order_status === 'accepted' ? 'Ajukan pembatalan?' : 'Batalkan permintaan?' }}</h2><p class="muted">{{ $order->order_status === 'accepted' ? 'Mitra akan meninjau permintaanmu.' : 'Pesanan belum diproses toko dan dapat dibatalkan.' }}</p>
                <form method="post" action="{{ route('orders.cancel', $order) }}" class="form-stack">@csrf<label>Alasan pembatalan<textarea name="cancellation_reason" rows="2" required maxlength="2000"></textarea></label><button class="button button-danger" type="submit">{{ $order->order_status === 'accepted' ? 'Kirim permintaan pembatalan' : 'Batalkan pesanan' }}</button></form>
            </section>
        @elseif ($order->order_status === 'cancellation_requested')
            <section class="panel"><span class="eyebrow">Permintaan pembatalan</span><h2>Menunggu keputusan toko</h2><p>{{ $order->cancellation_reason }}</p></section>
        @endif
    </div>
    <aside class="panel status-panel"><span class="eyebrow">Perjalanan pesanan</span><h2>{{ $labels[$order->order_status] ?? 'Status pesanan' }}</h2><div class="order-timeline">
        <div class="timeline-step done"><span>✓</span><div><strong>Permintaan dibuat</strong><small>{{ $order->created_at->format('d M, H:i') }}</small></div></div>
        <div class="timeline-step {{ in_array($order->order_status, ['pending','accepted','preparing','ready_for_pickup','on_delivery','completed'], true) ? 'done' : '' }}"><span>{{ in_array($order->order_status, ['pending','accepted','preparing','ready_for_pickup','on_delivery','completed'], true) ? '✓' : '2' }}</span><div><strong>{{ $order->delivery_method === 'delivery' ? 'Jarak & total disetujui' : 'Menunggu konfirmasi toko' }}</strong><small>{{ $order->delivery_method === 'delivery' && $order->order_status === 'awaiting_distance' ? 'Mitra memeriksa rute' : 'Pembaruan akan tampil di sini' }}</small></div></div>
        <div class="timeline-step {{ in_array($order->order_status, ['accepted','preparing','ready_for_pickup','on_delivery','completed'], true) ? 'done' : '' }}"><span>3</span><div><strong>Disiapkan</strong><small>Oleh Mitra</small></div></div>
        <div class="timeline-step {{ $order->order_status === 'completed' ? 'done' : '' }}"><span>{{ $order->order_status === 'completed' ? '✓' : '4' }}</span><div><strong>{{ $order->delivery_method === 'delivery' ? 'Diantar ke tujuan' : 'Selesai diambil' }}</strong><small>Semoga dinikmati ♡</small></div></div>
    </div><div class="support-note">Informasi driver dan pembaruan pesanan akan dicatat oleh Mitra di halaman ini.</div></aside>
</div>
@endsection
