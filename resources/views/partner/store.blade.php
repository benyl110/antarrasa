@extends('layouts.app')
@section('title', 'Profil toko')
@section('content')
<div class="page-heading"><span class="eyebrow"><span class="eyebrow-dot"></span> Profil usaha</span><h1>Ceritakan tentang <em>toko kamu.</em></h1><p class="muted">Informasi ini membantu pelanggan mengenal usaha dan menemukan lokasimu.</p></div>
<form method="post" action="{{ route('partner.store.update') }}" enctype="multipart/form-data" class="panel form-stack store-edit-form">
    @csrf @method('PUT')
    @if ($store->logo_path)<div class="current-image"><img src="{{ Storage::disk('public')->url($store->logo_path) }}" alt="Logo toko saat ini"><span>Logo saat ini</span></div>@endif
    <div class="form-grid">
        <label class="span-two">Nama toko<input name="store_name" value="{{ old('store_name', $store->store_name) }}" required></label>
        <label>Nomor telepon toko<input name="phone" value="{{ old('phone', $store->phone) }}" required></label>
        <label>Logo toko <span class="label-optional">(JPG/PNG/WEBP, maks. 3 MB)</span><input type="file" name="logo" accept="image/jpeg,image/png,image/webp" data-image-preview><span class="image-preview"></span></label>
        <label class="span-two">Deskripsi toko<textarea name="description" rows="3" maxlength="3000" placeholder="Apa yang membuat hidanganmu istimewa?">{{ old('description', $store->description) }}</textarea></label>
        <label class="span-two">Alamat lengkap<textarea name="address" rows="3" required>{{ old('address', $store->address) }}</textarea></label>
        <div class="span-two"><span class="eyebrow">Pembayaran transfer manual</span><p class="muted small">Isi rekening tujuan agar Konsumen dapat memilih transfer. Data ini ditampilkan pada detail pesanan.</p></div>
        <label>Nama bank<input name="bank_name" value="{{ old('bank_name', $store->bank_name) }}" maxlength="100" placeholder="Contoh: nama bank"></label>
        <label>Nama pemilik rekening<input name="bank_account_name" value="{{ old('bank_account_name', $store->bank_account_name) }}" maxlength="255"></label>
        <label class="span-two">Nomor rekening<input name="bank_account_number" value="{{ old('bank_account_number', $store->bank_account_number) }}" maxlength="100" inputmode="numeric"></label>
    </div>
    <div class="toggle-panel"><div><strong>Status operasional</strong><small>Jika tutup, toko tidak dapat menerima pesanan baru.</small></div><label class="switch"><input type="checkbox" name="is_open" value="1" {{ old('is_open', $store->is_open) ? 'checked' : '' }} {{ $store->store_status !== 'active' ? 'disabled' : '' }}><span></span></label></div>
    <div class="info-panel"><strong>Jangkauan delivery maksimal 5 km.</strong><p>Tarif tetap: Rp5.000 hingga 2 km, Rp8.000 untuk lebih dari 2 km hingga 5 km. Kebijakan ini berlaku sama untuk semua Mitra.</p></div>
    <div class="form-actions"><a class="button button-quiet" href="{{ route('partner.dashboard') }}">Batal</a><button class="button button-primary" type="submit">Simpan profil toko →</button></div>
</form>
@endsection
