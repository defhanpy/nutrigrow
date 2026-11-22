<!doctype html>
<html lang="id" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Masuk') — NUTRIGROW</title>
    <link rel="icon" type="image/png" href="{{ asset('images/nutrigrow-favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('boron/css/vendor.min.css') }}">
    <link rel="stylesheet" id="app-style" href="{{ asset('boron/css/app.min.css') }}">
    <link rel="stylesheet" href="{{ asset('boron/css/icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/nutrigrow.css') }}">
</head>
<body>
<div class="ng-auth">
    <aside class="ng-auth-side">
        <div>
            <a href="{{ url('/') }}" class="ng-brand">
                <img src="{{ asset('images/nutrigrow-mark.png') }}" alt="NUTRIGROW">
                <span>NUTRIGROW<small>Kemenkes RI · Posyandu</small></span>
            </a>
            <h1>Setiap anak tumbuh,<br>setiap keluarga terlibat.</h1>
            <p>Pantau pertumbuhan balita, kenali tanda yang perlu ditinjau, dan temukan ide menu gizi lokal — dalam satu layanan Posyandu terpadu.</p>
            <div class="ng-auth-badges">
                <span>Posyandu</span>
                <span>Puskesmas</span>
                <span>Kader &amp; Keluarga</span>
                <span>Gizi Lokal</span>
            </div>
        </div>
        <div class="ng-small" style="color:#d8f5e5;">
            <i class="ti ti-shield-heart"></i> Layanan bantu pemantauan pertumbuhan. Bukan pengganti pemeriksaan tenaga kesehatan.
        </div>
    </aside>
    <main class="ng-auth-main">
        <div class="ng-auth-card">
            @yield('content')
        </div>
    </main>
</div>
<script src="{{ asset('boron/js/vendor.min.js') }}"></script>
<script src="{{ asset('boron/js/config.js') }}"></script>
<script src="{{ asset('boron/js/app.js') }}"></script>
@stack('scripts')
@include('partials.flash-toast')
</body>
</html>
