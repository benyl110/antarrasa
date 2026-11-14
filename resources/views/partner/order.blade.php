@extends('layouts.app')
@section('title', 'Pesanan '.$order->order_code)
@section('content')
<a class="back-link" href="{{ route('partner.orders') }}">← Kembali ke pesanan</a>
@php
    $labels = [
        'awaiting_distance' => 'Menunggu verifikasi jarak',
        'awaiting_customer_confirmation' => 'Menunggu persetujuan Konsumen',
        'pending' => 'Menunggu konfirmasi toko',
        'accepted' => 'Pesanan diterima',
        'preparing' => 'Sedang disiapkan',
        'ready_for_pickup' => 'Siap diambil / diantar',
        'on_delivery' => 'Sedang diantar',
        'completed' => 'Selesai',
        'rejected' => 'Pesanan ditolak',
        'cancellation_requested' => 'Permintaan pembatalan',
        'cancelled' => 'Dibatalkan',
    ];
@endphp
<div class="detail-heading"><div><span class="eyebrow"><span class="eyebrow-dot"></span> {{ $order->order_code }}</span><h1>Pesanan <em>{{ $order->consumer->name }}.</em></h1><p class="muted">{{ $order->created_at->format('d M Y, H:i') }} · {{ $order->delivery_method === 'delivery' ? 'Delivery' : 'Pickup' }}</p></div><span class="status-badge status-{{ $order->order_status }}">{{ $labels[$order->order_status] ?? str($order->order_status)->replace('_', ' ')->title() }}</span></div>
<div class="checkout-grid order-detail-grid">
    <div class="panel-stack">
        <section class="panel">
            <div class="panel-heading"><div><span class="eyebrow">Rincian pesanan</span><h2>Menu yang dipilih</h2></div><span class="method-pill">{{ $order->payment_method === 'transfer' ? 'Transfer' : 'Tunai (COD)' }} · {{ str($order->payment_status)->replace('_', ' ')->title() }}</span></div>
            @foreach ($order->items as $item)
                <div class="summary-row item-summary"><span>{{ $item->product_name }} <small>× {{ $item->quantity }} · {{ 'Rp'.number_format($item->unit_price, 0, ',', '.') }} / item</small></span><strong>{{ 'Rp'.number_format($item->line_total, 0, ',', '.') }}</strong></div>
            @endforeach
            <div class="summary-row"><span>Subtotal menu</span><strong>{{ 'Rp'.number_format($order->subtotal, 0, ',', '.') }}</strong></div>
            <div class="summary-row"><span>Ongkir Konsumen{{ $order->delivery_distance_km ? ' · '.$order->delivery_distance_km.' km' : '' }}</span><strong>{{ 'Rp'.number_format($order->delivery_fee, 0, ',', '.') }}</strong></div>
            <div class="summary-total"><span>Total pesanan</span><strong>{{ 'Rp'.number_format($order->total, 0, ',', '.') }}</strong></div>
            <div class="detail-notes"><span>Kontak Konsumen</span><strong>{{ $order->consumer->name }} · {{ $order->consumer->phone }}</strong></div>
            @if ($order->recipient_name)<div class="detail-notes"><span>Alamat penerima</span><strong>{{ $order->recipient_name }} · {{ $order->recipient_phone }}<br>{{ $order->delivery_address }}</strong></div>@endif
            @if ($order->notes)<div class="detail-notes"><span>Catatan pesanan</span><strong>{{ $order->notes }}</strong></div>@endif
            @if ($order->cancellation_reason && in_array($order->order_status, ['rejected','cancelled'], true))<div class="info-panel"><strong>Catatan</strong><p>{{ $order->cancellation_reason }}</p></div>@endif
        </section>

        @if ($order->order_status === 'awaiting_distance')
            <section class="panel attention-panel"><span class="eyebrow">Verifikasi rute manual</span><h2>Periksa jarak jalan ke alamat</h2><p>Gunakan jarak rute perjalanan. Maksimum 5 km. Setelah dikirim, Konsumen akan meninjau ongkir dan total akhir.</p>
                <form method="post" action="{{ route('partner.orders.distance', $order) }}" class="inline-form">@csrf<label>Jarak terverifikasi (km)<input type="number" name="distance_km" min="0.1" max="100" step="0.1" placeholder="Contoh: 3.5" required></label><button class="button button-primary" type="submit">Hitung ongkir & kirim →</button></form>
                <small class="muted">≤2 km: Rp5.000 · >2–5 km: Rp8.000 · >5 km: pesanan ditolak.</small>
            </section>
        @endif
        @if ($order->order_status === 'pending')
            <section class="panel attention-panel"><span class="eyebrow">Tindakan diperlukan</span><h2>Terima pesanan ini?</h2><p>Pastikan menu dan kapasitas dapur siap. Pesanan yang ditolak akan mengembalikan stok secara otomatis.</p>
                <div class="form-actions"><form method="post" action="{{ route('partner.orders.decision', $order) }}">@csrf<input type="hidden" name="decision" value="reject"><button class="button button-danger" type="submit" data-confirm="Tolak pesanan ini? Stok akan dikembalikan.">Tolak pesanan</button></form><form method="post" action="{{ route('partner.orders.decision', $order) }}">@csrf<input type="hidden" name="decision" value="accept"><button class="button button-primary" type="submit">Terima pesanan ✓</button></form></div>
            </section>
        @endif
        @if (isset(['accepted' => 1, 'preparing' => 1, 'ready_for_pickup' => 1, 'on_delivery' => 1][$order->order_status]))
            @php
                $nextStatus = match ($order->order_status) {
                    'accepted' => 'preparing',
                    'preparing' => 'ready_for_pickup',
                    'ready_for_pickup' => $order->delivery_method === 'delivery' ? 'on_delivery' : 'completed',
                    'on_delivery' => 'completed',
                    default => null,
                };
                $nextLabel = match ($nextStatus) {
                    'preparing' => 'Mulai siapkan pesanan',
                    'ready_for_pickup' => 'Tandai siap',
                    'on_delivery' => 'Tandai sedang diantar',
                    'completed' => 'Tandai selesai',
                    default => 'Perbarui status',
                };
            @endphp
            @if ($nextStatus)
                <section class="panel"><span class="eyebrow">Langkah berikutnya</span><h2>{{ $labels[$order->order_status] }}</h2><form method="post" action="{{ route('partner.orders.status', $order) }}">@csrf<input type="hidden" name="order_status" value="{{ $nextStatus }}"><button class="button button-primary" type="submit">{{ $nextLabel }} →</button></form></section>
            @endif
        @endif
        @if ($order->order_status === 'cancellation_requested')
            <section class="panel attention-panel"><span class="eyebrow">Permintaan Konsumen</span><h2>Pertimbangkan pembatalan</h2><p>{{ $order->cancellation_reason }}</p><div class="form-actions"><form method="post" action="{{ route('partner.orders.cancellation', $order) }}">@csrf<input type="hidden" name="decision" value="reject"><button class="button button-outline" type="submit">Tolak permintaan</button></form><form method="post" action="{{ route('partner.orders.cancellation', $order) }}">@csrf<input type="hidden" name="decision" value="approve"><button class="button button-danger" type="submit" data-confirm="Setujui pembatalan? Stok akan dikembalikan jika telah dicadangkan.">Setujui pembatalan</button></form></div></section>
        @endif
        @if ($order->payment_method === 'transfer' && $order->payment_status === 'awaiting_verification')
            <section class="panel"><span class="eyebrow">Verifikasi manual</span><h2>Periksa bukti transfer</h2><p class="muted">Pastikan dana benar-benar masuk ke rekening toko sebelum menandai pembayaran diterima.</p><a class="button button-outline" href="{{ route('partner.orders.payment-proof', $order) }}">Buka bukti transfer ↗</a>
                <form method="post" action="{{ route('partner.orders.payment', $order) }}" class="form-stack payment-review-form">@csrf
                    <label>Keputusan<select name="decision" required><option value="paid">Dana sudah diterima</option><option value="rejected">Tolak bukti</option></select></label>
                    <label>Alasan penolakan <span class="label-optional">(isi jika bukti ditolak)</span><textarea name="payment_rejection_reason" rows="2" maxlength="2000"></textarea></label>
                    <button class="button button-primary" type="submit">Simpan verifikasi</button>
                </form>
            </section>
        @endif
        @if ($order->payment_status === 'refund_pending' && $order->order_status === 'cancelled')
            <section class="panel"><span class="eyebrow">Pengembalian manual</span><h2>Catat pengembalian dana</h2><p class="muted">Refund dilakukan Mitra di luar sistem. Simpan catatan setelah dana dikembalikan.</p><form method="post" action="{{ route('partner.orders.refund', $order) }}" class="form-stack">@csrf<label>Catatan refund<textarea name="refund_note" rows="2" required maxlength="2000"></textarea></label><button class="button button-primary" type="submit">Konfirmasi refund selesai</button></form></section>
        @endif
        @if ($order->payment_method === 'cod' && $order->payment_status === 'unpaid' && $order->order_status === 'completed')
            <section class="panel"><span class="eyebrow">Pembayaran tunai</span><h2>Konfirmasi pembayaran diterima</h2><form method="post" action="{{ route('partner.orders.cod-payment', $order) }}">@csrf<button class="button button-primary" type="submit">Tandai sudah dibayar</button></form></section>
        @endif
    </div>
    <aside class="panel-stack">
        @if ($order->delivery_method === 'delivery' && in_array($order->order_status, ['accepted','preparing','ready_for_pickup','on_delivery'], true))
            <section class="panel"><span class="eyebrow">Pengantaran manual</span><h2>Informasi driver</h2><p class="muted">Biaya aktual driver adalah tanggungan toko dan tidak mengubah ongkir Konsumen.</p>
                <form method="post" action="{{ route('partner.orders.driver', $order) }}" class="form-stack">@csrf @method('PUT')
                    <label>Status pemesanan driver<select name="driver_booking_status" required>@foreach (['not_booked' => 'Belum dipesan', 'contacting' => 'Menghubungi driver', 'booked' => 'Driver dipesan', 'failed' => 'Tidak berhasil'] as $value => $label)<option value="{{ $value }}" @selected(old('driver_booking_status', $order->driver_booking_status) === $value)>{{ $label }}</option>@endforeach</select></label>
                    <label>Nama driver<input name="driver_name" value="{{ old('driver_name', $order->driver_name) }}" maxlength="255"></label>
                    <label>Nomor telepon driver<input name="driver_phone" value="{{ old('driver_phone', $order->driver_phone) }}" maxlength="30"></label>
                    <label>Biaya aktual driver (Rp)<input type="number" name="driver_cost" value="{{ old('driver_cost', $order->driver_cost) }}" min="0" max="100000000"></label>
                    <button class="button button-outline button-full" type="submit">Simpan informasi driver</button>
                </form>
                <div class="separation-note">Ongkir Konsumen: <strong>{{ 'Rp'.number_format($order->delivery_fee, 0, ',', '.') }}</strong><br>Biaya driver toko: <strong>{{ $order->driver_cost === null ? 'Belum dicatat' : 'Rp'.number_format($order->driver_cost, 0, ',', '.') }}</strong></div>
            </section>
        @endif
        <section class="panel status-panel"><span class="eyebrow">Ringkasan status</span><h2>{{ $labels[$order->order_status] ?? 'Pesanan' }}</h2><div class="detail-notes"><span>Pembayaran</span><strong>{{ str($order->payment_status)->replace('_', ' ')->title() }}</strong></div><div class="detail-notes"><span>Terakhir diperbarui</span><strong>{{ $order->updated_at->format('d M Y, H:i') }}</strong></div></section>
    </aside>
</div>
@endsection
