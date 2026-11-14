@extends('layouts.app')
@section('title', 'Jelajahi toko')
@section('content')
<section class="page-heading heading-with-aside">
    <div><span class="eyebrow"><span class="eyebrow-dot"></span> Dari dapur sekitar</span><h1>Hari ini mau <em>makan apa?</em></h1><p class="muted">Temukan hidangan rumahan dan cerita para pelaku usaha di dekatmu.</p></div>
    <div class="heading-stamp"><span>♡</span> Dukung usaha<br>lokal hari ini</div>
</section>
<form method="get" action="{{ route('consumer.home') }}" class="search-bar">
    <span aria-hidden="true">⌕</span><input type="search" name="q" value="{{ $search }}" placeholder="Cari nama toko atau hidangan..." aria-label="Cari toko">
    <button class="button button-dark" type="submit">Cari</button>
</form>
<div class="section-title-row"><div><h2>Toko yang sedang buka</h2><p class="muted">Pilih satu toko untuk mulai mengisi keranjang.</p></div><span class="result-count">{{ $stores->total() }} toko</span></div>
@if ($stores->isEmpty())
    <div class="empty-state"><span class="empty-icon">⌕</span><h3>{{ $search ? 'Belum ada toko yang cocok.' : 'Belum ada toko yang buka.' }}</h3><p>{{ $search ? 'Coba kata pencarian lain.' : 'Silakan mampir lagi nanti. Toko yang aktif akan tampil di sini.' }}</p></div>
@else
    <div class="store-grid">
        @foreach ($stores as $store)
            <a class="store-card" href="{{ route('consumer.store', $store) }}">
                <div class="store-card-art">
                    @if ($store->logo_path)<img src="{{ Storage::disk('public')->url($store->logo_path) }}" alt="Logo {{ $store->store_name }}">
                    @else <span class="store-placeholder">{{ mb_substr($store->store_name, 0, 1) }}</span>@endif
                    <span class="open-pill"><i></i> Buka</span>
                </div>
                <div class="store-card-info"><div><h3>{{ $store->store_name }}</h3><p>{{ $store->products_count }} menu tersedia</p></div><span class="card-arrow">↗</span></div>
                @if ($store->description)<p class="store-description">{{ Str::limit($store->description, 90) }}</p>@endif
            </a>
        @endforeach
    </div>
    <div class="pagination-wrap">{{ $stores->links() }}</div>
@endif
@endsection
