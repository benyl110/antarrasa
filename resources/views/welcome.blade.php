<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#fffaf1">
    <title>AntarRasa — Cerita baik, dari dapur sekitar</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('images/antarasa-logo.jpg') }}">
    <link rel="stylesheet" href="{{ asset('css/antarasa.css') }}">
</head>
<body class="landing">
    <header class="landing-nav wrap">
        <a class="brand" href="{{ route('home') }}">
            <img src="{{ asset('images/antarasa-logo.jpg') }}" alt="" class="brand-mark">
            <span>antar<span class="brand-accent">rasa</span></span>
        </a>
        <a class="button button-outline" href="{{ route('login', 'konsumen') }}">Masuk</a>
    </header>

    <main>
        <section class="hero wrap">
            <div class="hero-copy">
                <span class="eyebrow"><span class="eyebrow-dot"></span> Rasa lokal, dekat di hati</span>
                <h1>Temukan rasa,<br><em>temukan cerita.</em></h1>
                <p class="hero-lead">Pesan hidangan favorit dari UMKM kuliner di sekitarmu. Hangat dari dapur mereka, sampai di mejamu.</p>
                <div class="hero-actions">
                    <a class="button button-primary button-large" href="{{ route('register', 'konsumen') }}">Mulai pesan <span aria-hidden="true">↗</span></a>
                    <a class="text-link" href="{{ route('register', 'mitra') }}">Punya usaha kuliner? <span>Gabung sebagai Mitra</span></a>
                </div>
                <div class="hero-note"><span class="note-icon">♡</span> Setiap pesanan mendukung usaha lokal.</div>
            </div>
            <div class="hero-art" aria-label="Logo AntarRasa">
                <div class="art-orbit orbit-one"></div>
                <div class="art-orbit orbit-two"></div>
                <span class="art-spark spark-one">✳</span><span class="art-spark spark-two">✦</span>
                <div class="art-logo"><img src="{{ asset('images/antarasa-logo.jpg') }}" alt="Ikon AntarRasa"></div>
                <div class="art-tag"><span class="tag-heart">♥</span><span><strong>Dimasak dengan hati</strong><small>oleh tetangga kita</small></span></div>
            </div>
        </section>

        <section class="landing-bottom">
            <div class="wrap landing-bottom-inner">
                <div><span class="bottom-kicker">Dibuat untuk dua sisi kebaikan</span><h2>Satu tempat, banyak cerita rasa.</h2></div>
                <div class="role-cards">
                    <div class="role-card">
                        <span class="role-icon role-icon-peach">⌕</span>
                        <div class="role-card-copy"><strong>Untuk Konsumen</strong><small>Jelajahi menu dan pesan dari toko lokal.</small><div class="role-links"><a href="{{ route('register', 'konsumen') }}">Daftar</a><a href="{{ route('login', 'konsumen') }}">Sudah punya akun? Masuk →</a></div></div>
                    </div>
                    <div class="role-card">
                        <span class="role-icon role-icon-green">✿</span>
                        <div class="role-card-copy"><strong>Untuk Mitra</strong><small>Kelola toko dan layani lebih banyak pelanggan.</small><div class="role-links"><a href="{{ route('register', 'mitra') }}">Daftar</a><a href="{{ route('login', 'mitra') }}">Sudah punya akun? Masuk →</a></div></div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    <footer class="landing-footer wrap"><span>© {{ date('Y') }} AntarRasa</span><span>Rasa dekat, cerita hangat.</span></footer>
</body>
</html>
