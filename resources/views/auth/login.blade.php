@extends('layouts.app')
@section('title', 'Masuk')
@section('content')
<section class="auth-shell">
    <a class="back-link" href="{{ route('home') }}">← Kembali ke beranda</a>
    <div class="auth-card">
        <span class="eyebrow"><span class="eyebrow-dot"></span> {{ $role === 'mitra' ? 'Ruang Mitra' : 'Ruang Konsumen' }}</span>
        <h1>Senang melihatmu<br>kembali.</h1>
        <p class="muted">Masuk untuk melanjutkan cerita rasamu di AntarRasa.</p>
        <form method="post" action="{{ route('login.store', $role) }}" class="form-stack">
            @csrf
            <label>Email atau username<input name="identity" value="{{ old('identity') }}" autocomplete="username" required autofocus placeholder="nama@email.com"></label>
            <label>Kata sandi<input type="password" name="password" autocomplete="current-password" required placeholder="Masukkan kata sandi"></label>
            <label class="check-row"><input type="checkbox" name="remember" value="1"> Ingat saya di perangkat ini</label>
            <button class="button button-primary button-full" type="submit">Masuk sebagai {{ ucfirst($role) }} <span>→</span></button>
        </form>
        <p class="auth-switch">Belum punya akun? <a href="{{ route('register', $role) }}">Daftar sebagai {{ ucfirst($role) }}</a></p>
        @if ($role === 'mitra')
            <p class="auth-footnote">Pengajuan Mitra perlu aktivasi sebelum dapat masuk.</p>
        @endif
    </div>
</section>
@endsection
