@extends('layouts.app')
@section('title', 'Perbarui kata sandi')
@section('content')
<section class="auth-shell">
    <div class="auth-card">
        <span class="eyebrow"><span class="eyebrow-dot"></span> Keamanan akun</span>
        <h1>Buat kata sandi<br><em>yang baru.</em></h1>
        <p class="muted">Kata sandi sementara perlu diganti sebelum menggunakan AntarRasa.</p>
        <form method="post" action="{{ route('account.password.update') }}" class="form-stack">
            @csrf @method('PUT')
            <label>Kata sandi saat ini<input type="password" name="current_password" autocomplete="current-password" required></label>
            <label>Kata sandi baru<input type="password" name="password" autocomplete="new-password" minlength="8" required></label>
            <label>Ulangi kata sandi baru<input type="password" name="password_confirmation" autocomplete="new-password" minlength="8" required></label>
            <button class="button button-primary button-full" type="submit">Simpan kata sandi</button>
        </form>
    </div>
</section>
@endsection
