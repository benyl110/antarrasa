@extends('layouts.app')
@section('title', 'Checkout')
@section('content')
@php($subtotal = 0)
@php($transferReady = $store->bank_name && $store->bank_account_name && $store->bank_account_number)
@foreach ($cart['items'] as $id => $quantity) @if ($products->has($id)) @php($subtotal += $products->get($id)->price * $quantity) @endif @endforeach
<div class="page-heading"><a class="back-link" href="{{ route('cart.show') }}">← Kembali ke keranjang</a><span class="eyebrow"><span class="eyebrow-dot"></span> Langkah terakhir</span><h1>Atur pesanan <em>terbaikmu.</em></h1><p class="muted">Pesanan dari {{ $store->store_name }}. Ongkir delivery menunggu konfirmasi jarak dari Mitra.</p></div>
<form method="post" action="{{ route('checkout.store') }}" class="checkout-grid">
    @csrf
    <input type="hidden" name="checkout_token" value="{{ $checkoutToken }}">
    <section class="panel form-stack">
        <div><span class="eyebrow">01 · Cara menerima</span><h2>Pilih metode pemenuhan</h2></div>
        <div class="choice-grid">
            <label class="choice-card"><input type="radio" name="delivery_method" value="delivery" {{ old('delivery_method', 'delivery') === 'delivery' ? 'checked' : '' }}><span class="choice-icon">⌖</span><span><strong>Diantar</strong><small>Jarak diverifikasi toko, maksimal 5 km.</small></span></label>
            <label class="choice-card"><input type="radio" name="delivery_method" value="pickup" {{ old('delivery_method') === 'pickup' ? 'checked' : '' }}><span class="choice-icon">↗</span><span><strong>Ambil sendiri</strong><small>Tanpa biaya pengantaran.</small></span></label>
        </div>
        <div class="form-grid delivery-fields">
            <label>Nama penerima<input name="recipient_name" value="{{ old('recipient_name', auth()->user()->name) }}" autocomplete="name"></label>
            <label>Nomor telepon penerima<input name="recipient_phone" value="{{ old('recipient_phone', auth()->user()->phone) }}" autocomplete="tel"></label>
            <label class="span-two">Alamat lengkap dan patokan<textarea name="delivery_address" rows="3" placeholder="Jalan, nomor rumah, RT/RW, patokan...">{{ old('delivery_address') }}</textarea></label>
        </div>
        <div><span class="eyebrow">02 · Pembayaran</span><h2>Pilih cara membayar</h2></div>
        <div class="choice-grid">
            <label class="choice-card {{ ! $transferReady ? 'choice-disabled' : '' }}"><input type="radio" name="payment_method" value="transfer" {{ $transferReady && old('payment_method', 'transfer') === 'transfer' ? 'checked' : '' }} {{ ! $transferReady ? 'disabled' : '' }}><span class="choice-icon">⇄</span><span><strong>Transfer manual</strong><small>{{ $transferReady ? 'Unggah bukti transfer untuk diverifikasi Mitra.' : 'Belum tersedia; toko belum mengatur rekening transfer.' }}</small></span></label>
            <label class="choice-card"><input type="radio" name="payment_method" value="cod" {{ ! $transferReady || old('payment_method') === 'cod' ? 'checked' : '' }}><span class="choice-icon">Rp</span><span><strong>Tunai (COD)</strong><small>Bayar langsung saat pesanan diterima.</small></span></label>
        </div>
        <label>Catatan untuk toko <span class="label-optional">(opsional)</span><textarea name="notes" rows="2" placeholder="Misalnya: tidak pedas, sambal dipisah...">{{ old('notes') }}</textarea></label>
    </section>
    <aside class="panel summary-panel"><span class="eyebrow">Rincian biaya</span><h2>{{ $store->store_name }}</h2>
        @foreach ($cart['items'] as $id => $quantity) @if ($products->has($id)) @php($product = $products->get($id))<div class="summary-row"><span>{{ $product->name }} × {{ $quantity }}</span><strong>{{ 'Rp'.number_format($product->price * $quantity, 0, ',', '.') }}</strong></div>@endif @endforeach
        <div class="summary-row"><span>Subtotal menu</span><strong>{{ 'Rp'.number_format($subtotal, 0, ',', '.') }}</strong></div><div class="summary-row"><span>Biaya pengantaran</span><span class="muted">Sesuai jarak rute</span></div><div class="summary-total"><span>Total sementara</span><strong>{{ 'Rp'.number_format($subtotal, 0, ',', '.') }}</strong></div>
        <p class="summary-note">Untuk delivery, pesanan aktif setelah kamu menyetujui jarak dan total akhir. Tarif: Rp5.000 (≤2 km), Rp8.000 (>2–5 km).</p>
        <button class="button button-primary button-full" type="submit">Kirim permintaan pesanan →</button>
    </aside>
</form>
@endsection
