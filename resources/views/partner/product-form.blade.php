@extends('layouts.app')
@section('title', $product->exists ? 'Ubah menu' : 'Tambah menu')
@section('content')
<div class="page-heading"><a class="back-link" href="{{ route('partner.products') }}">← Kembali ke menu</a><span class="eyebrow"><span class="eyebrow-dot"></span> Katalog toko</span><h1>{{ $product->exists ? 'Perbarui' : 'Tambahkan' }} menu <em>pilihanmu.</em></h1></div>
<form method="post" action="{{ $product->exists ? route('partner.products.update', $product) : route('partner.products.store') }}" enctype="multipart/form-data" class="panel form-stack store-edit-form">
    @csrf
    @if ($product->exists) @method('PUT') @endif
    @if ($product->image_path)<div class="current-image"><img src="{{ Storage::disk('public')->url($product->image_path) }}" alt="Foto {{ $product->name }}"><span>Foto saat ini</span></div>@endif
    <div class="form-grid">
        <label class="span-two">Nama menu<input name="name" value="{{ old('name', $product->name) }}" required maxlength="255"></label>
        <label>Harga (rupiah)<input type="number" name="price" value="{{ old('price', $product->price) }}" min="1" max="100000000" required></label>
        <label>Stok tersedia<input type="number" name="stock_qty" value="{{ old('stock_qty', $product->stock_qty ?? 0) }}" min="0" max="1000000" required></label>
        <label>Ketersediaan<select name="availability_status" required><option value="available" @selected(old('availability_status', $product->availability_status ?? 'available') === 'available')>Tersedia</option><option value="unavailable" @selected(old('availability_status', $product->availability_status ?? 'available') === 'unavailable')>Tidak tersedia</option></select></label>
        <label>Foto menu <span class="label-optional">(JPG/PNG/WEBP, maks. 3 MB)</span><input type="file" name="image" accept="image/jpeg,image/png,image/webp" data-image-preview><span class="image-preview"></span></label>
        <label class="span-two">Deskripsi<textarea name="description" rows="4" maxlength="3000" placeholder="Ceritakan bahan, rasa, atau cara penyajian menu ini.">{{ old('description', $product->description) }}</textarea></label>
    </div>
    <div class="form-actions"><a class="button button-quiet" href="{{ route('partner.products') }}">Batal</a><button class="button button-primary" type="submit">Simpan menu →</button></div>
</form>
@endsection
