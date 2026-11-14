@extends('layouts.app')
@section('title', 'Daftar')
@section('content')
<section class="auth-shell auth-shell-wide">
    <a class="back-link" href="{{ route('home') }}">← Kembali ke beranda</a>
    <div class="auth-card">
        <span class="eyebrow"><span class="eyebrow-dot"></span> Bergabung sebagai {{ ucfirst($role) }}</span>
        <h1>{{ $role === 'mitra' ? 'Buka pintu untuk' : 'Selamat datang di' }}<br><em>{{ $role === 'mitra' ? 'lebih banyak rasa.' : 'AntarRasa.' }}</em></h1>
        <p class="muted">{{ $role === 'mitra' ? 'Kirim pengajuan toko. Tim AntarRasa akan mengaktifkan akun sebelum digunakan.' : 'Buat akun untuk menemukan hidangan favorit dari usaha di sekitarmu.' }}</p>
        <form method="post" action="{{ route('register.store', $role) }}" class="form-stack">
            @csrf
            <div class="form-grid">
                <label>Nama lengkap<input name="name" value="{{ old('name') }}" autocomplete="name" required></label>
                <label>Nomor telepon<input name="phone" value="{{ old('phone') }}" autocomplete="tel" inputmode="tel" required></label>
                <label>Email<input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required></label>
                <label>Username <span class="label-optional">(opsional)</span><input name="username" value="{{ old('username') }}" autocomplete="username"></label>
                @if ($role === 'mitra')
                    <label class="span-two">Nama usaha<input name="store_name" value="{{ old('store_name') }}" required></label>
                    <label class="span-two">Alamat usaha<textarea name="address" rows="2" required>{{ old('address') }}</textarea></label>
                @endif
                <label>Kata sandi<input type="password" name="password" autocomplete="new-password" required minlength="8"></label>
                <label>Ulangi kata sandi<input type="password" name="password_confirmation" autocomplete="new-password" required minlength="8"></label>
            </div>
            <button class="button button-primary button-full" type="submit">Buat akun <span>→</span></button>
        </form>
        <p class="auth-switch">Sudah punya akun? <a href="{{ route('login', $role) }}">Masuk di sini</a></p>
    </div>
</section>
@endsection
