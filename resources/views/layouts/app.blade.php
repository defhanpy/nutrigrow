@php
    $user = auth()->user();
    $navigation = \App\Support\PortalNavigation::for($user);
    $portalTitle = match ($user->role) {
        \App\Enums\Role::Kader => 'Ruang Kerja Kader',
        \App\Enums\Role::OrangTua => 'Ruang Keluarga',
        \App\Enums\Role::TenagaKesehatan => 'Ruang Tenaga Kesehatan',
        \App\Enums\Role::AdminPuskesmas => 'Ruang Puskesmas',
    };
@endphp
<!doctype html>
<html lang="id" data-bs-theme="light" data-layout="vertical" data-layout-mode="fluid" data-topbar-color="light" data-menu-color="dark" data-sidenav-size="default">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="NUTRIGROW — layanan pemantauan pertumbuhan dan gizi balita untuk Posyandu dan Puskesmas.">
    <title>@yield('title', $portalTitle) — NUTRIGROW</title>
    <link rel="icon" type="image/png" href="{{ asset('images/nutrigrow-favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('boron/css/vendor.min.css') }}">
    <link rel="stylesheet" id="app-style" href="{{ asset('boron/css/app.min.css') }}">
    <link rel="stylesheet" href="{{ asset('boron/css/icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/nutrigrow.css') }}">
    @stack('styles')
</head>
<body>
<div class="wrapper">
    <div class="sidenav-menu">
        <div class="logo">
            <a href="{{ route($user->homeRoute()) }}" class="ng-brand">
                <img src="{{ asset('images/nutrigrow-mark.png') }}" alt="NUTRIGROW">
                <span>
                    NUTRIGROW
                    <small>Kemenkes RI · Posyandu</small>
                </span>
            </a>
        </div>
        <div data-simplebar class="h-100">
            <ul class="side-nav">
                <li class="side-nav-title">Menu {{ $portalTitle }}</li>
                @foreach ($navigation as $item)
                    <li class="side-nav-item">
                        <a href="{{ $item['url'] }}" class="side-nav-link {{ in_array(request()->route()->getName(), $item['active'], true) ? 'active' : '' }}">
                            <span class="menu-icon"><i class="ti {{ $item['icon'] }}"></i></span>
                            <span class="menu-text">{{ $item['label'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
            <div class="ng-side-note">
                <strong><i class="ti ti-heart-handshake"></i> Pendampingan keluarga</strong>
                Setiap angka adalah cerita anak. Catat dengan teliti, sampaikan dengan hangat.
            </div>
        </div>
    </div>

    <div class="app-topbar">
        <div class="topbar-menu">
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-sm sidenav-toggle-button d-lg-none" type="button" aria-label="Buka menu">
                    <i class="ti ti-menu-2"></i>
                </button>
                <div class="ng-topbar-context">
                    <div>
                        <div class="ng-org">Kementerian Kesehatan RI · Posyandu Terintegrasi</div>
                        <div class="ng-portal">{{ $portalTitle }} — {{ $user->facility?->name ?? 'Layanan Posyandu' }}</div>
                    </div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <div class="dropdown">
                    <a href="#" class="ng-user-chip dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="ng-avatar">{{ $user->initials() }}</span>
                        <span class="ng-user-meta">
                            <strong>{{ $user->name }}</strong>
                            <span>{{ $user->roleLabel() }}</span>
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li class="px-2 py-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="ng-avatar">{{ $user->initials() }}</span>
                                <div>
                                    <div class="fw-bold ng-small">{{ $user->name }}</div>
                                    <span class="badge badge-soft-success mt-1">{{ $user->roleLabel() }}</span>
                                </div>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="{{ route('profile.show') }}"><i class="ti ti-user-circle me-2"></i>Profil saya</a></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger"><i class="ti ti-logout me-2"></i>Keluar</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="page-content">
        <div class="page-container">
            @yield('content')
        </div>
        <footer class="ng-footer">
            <div>
                <strong>NUTRIGROW</strong> — Layanan Posyandu Terintegrasi · Kementerian Kesehatan RI
            </div>
            <div>
                <i class="ti ti-shield-heart"></i> Alat bantu pemantauan, bukan pengganti pemeriksaan tenaga kesehatan.
            </div>
        </footer>
    </div>
</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="ngToast" class="toast ng-toast" role="status" aria-live="polite" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="ngToastBody"></div>
            <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Tutup"></button>
        </div>
    </div>
</div>

<script src="{{ asset('boron/js/vendor.min.js') }}"></script>
<script src="{{ asset('boron/js/config.js') }}"></script>
<script src="{{ asset('boron/js/app.js') }}"></script>
@stack('scripts')
<script>
    window.NG = {
        showToast(message) {
            const body = document.getElementById('ngToastBody');
            const el = document.getElementById('ngToast');
            if (!body || !el || !window.bootstrap) return;
            body.textContent = message;
            window.bootstrap.Toast.getOrCreateInstance(el, { delay: 4000 }).show();
        }
    };
    document.addEventListener('DOMContentLoaded', () => {
        @if (session('status'))
            window.NG.showToast(@json(session('status')));
        @endif
        @if ($errors->any())
            window.NG.showToast(@json($errors->first()));
        @endif
    });
</script>
</body>
</html>
