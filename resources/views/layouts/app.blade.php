<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#fffaf1">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="AntarRasa">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title>@yield('title', 'AntarRasa') — AntarRasa</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('images/antarasa-logo.jpg') }}">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/antarasa-apple-touch-icon.png') }}">
    <link rel="stylesheet" href="{{ asset('css/antarasa.css') }}">
</head>
<body class="app-body">
    <header class="app-header">
        <div class="wrap header-inner">
            <a class="brand" href="{{ route('dashboard') }}">
                <img src="{{ asset('images/antarasa-logo.jpg') }}" alt="" class="brand-mark">
                <span>antar<span class="brand-accent">rasa</span></span>
            </a>
            @auth
                <nav class="main-nav" aria-label="Navigasi utama">
                    @if (auth()->user()->role === 'mitra')
                        <a href="{{ route('partner.dashboard') }}" @class(['active' => request()->routeIs('partner.dashboard')])>Ringkasan</a>
                        <a href="{{ route('partner.products') }}" @class(['active' => request()->routeIs('partner.products*')])>Menu</a>
                        <a href="{{ route('partner.orders') }}" @class(['active' => request()->routeIs('partner.orders*')])>Pesanan</a>
                        <a href="{{ route('partner.store.edit') }}" @class(['active' => request()->routeIs('partner.store.*')])>Toko</a>
                    @else
                        <a href="{{ route('consumer.home') }}" @class(['active' => request()->routeIs('consumer.*')])>Jelajah</a>
                        <a href="{{ route('cart.show') }}" @class(['active' => request()->routeIs('cart.*', 'checkout*')])>Keranjang</a>
                        <a href="{{ route('orders.index') }}" @class(['active' => request()->routeIs('orders.*')])>Pesanan saya</a>
                    @endif
                </nav>
                <div class="header-user">
                    <div class="user-avatar">{{ mb_substr(auth()->user()->name, 0, 1) }}</div>
                    <span class="user-name">{{ auth()->user()->name }}</span>
                    <form method="post" action="{{ route('logout') }}">@csrf<button class="nav-logout" type="submit">Keluar</button></form>
                </div>
            @else
                <a class="button button-outline button-small" href="{{ route('home') }}">Kembali</a>
            @endauth
        </div>
    </header>

    <main class="wrap page-wrap">
        @if (session('status'))
            <div class="flash flash-success" role="status"><span>✓</span>{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="flash flash-error" role="alert">
                <span>!</span><div><strong>Periksa kembali ya.</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            </div>
        @endif
        @yield('content')
    </main>

    <footer class="app-footer wrap"><span>© {{ date('Y') }} AntarRasa</span><span>Rasa dekat, cerita hangat.</span></footer>
    <script src="{{ asset('js/antarasa.js') }}" defer></script>
</body>
</html>
