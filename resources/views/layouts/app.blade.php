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
    <link rel="stylesheet" href="{{ asset('boron/vendor/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('boron/vendor/flatpickr/flatpickr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/nutrigrow.css') }}">
    @stack('styles')
</head>
<body>
<a href="#konten-utama" class="ng-skip-link">Langsung ke konten utama</a>
<div class="wrapper">
    <div class="sidenav-menu">
        <div class="logo">
            <a href="{{ route($user->homeRoute()) }}" class="ng-brand" aria-label="NUTRIGROW, kembali ke beranda">
                <img src="{{ asset('images/nutrigrow-mark.svg') }}" alt="Logo NUTRIGROW" class="ng-brand-icon">
                <div class="ng-brand-text">
                    <span class="ng-brand-name">NUTRIGROW</span>
                    <small class="ng-brand-badge">Standar WHO · Posyandu Komunitas</small>
                </div>
            </a>
            <button class="button-close-fullsidebar d-lg-none" type="button" aria-label="Tutup menu navigasi">
                <i class="ti ti-x align-middle"></i>
            </button>
        </div>
        <div data-simplebar class="d-flex flex-column justify-content-between h-100">
            <nav aria-label="Menu utama {{ $portalTitle }}">
                <ul class="side-nav">
                    <li class="side-nav-title">Menu {{ $portalTitle }}</li>
                    @foreach ($navigation as $item)
                        @php($isActive = in_array(request()->route()->getName(), $item['active'], true))
                        <li class="side-nav-item">
                            <a href="{{ $item['url'] }}" class="side-nav-link {{ $isActive ? 'active' : '' }}" @if ($isActive) aria-current="page" @endif>
                                <span class="menu-icon" aria-hidden="true"><i class="ti {{ $item['icon'] }}"></i></span>
                                <span class="menu-text">{{ $item['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
            <div class="ng-sidebar-footer">
                <div class="ng-sidebar-facility">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="ng-live-pulse" aria-hidden="true"></span>
                        <strong class="ng-facility-label">Faskes Aktif</strong>
                    </div>
                    <div class="ng-facility-name">{{ $user->facility?->name ?? 'Puskesmas Sukamaju' }}</div>
                    <div class="ng-facility-sub">{{ $user->roleLabel() }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="app-topbar">
        <div class="topbar-menu">
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-sm sidenav-toggle-button d-lg-none" type="button" aria-label="Buka menu navigasi" aria-controls="konten-utama" aria-expanded="false">
                    <i class="ti ti-menu-2" aria-hidden="true"></i>
                </button>
                <div class="ng-topbar-context">
                    <div>
                        <div class="ng-org">Standar Antropometri WHO · Layanan Posyandu</div>
                        <div class="ng-portal">{{ $portalTitle }} — {{ $user->facility?->name ?? 'Layanan Posyandu' }}</div>
                    </div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <div class="dropdown">
                    <a href="#" class="ng-user-chip dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Menu akun {{ $user->name }}">
                        <span class="ng-avatar" aria-hidden="true">{{ $user->initials() }}</span>
                        <span class="ng-user-meta">
                            <strong>{{ $user->name }}</strong>
                            <span>{{ $user->roleLabel() }}</span>
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li class="px-2 py-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="ng-avatar" aria-hidden="true">{{ $user->initials() }}</span>
                                <div>
                                    <div class="fw-bold ng-small">{{ $user->name }}</div>
                                    <span class="badge badge-soft-success mt-1">{{ $user->roleLabel() }}</span>
                                </div>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="{{ route('profile.show') }}"><i class="ti ti-user-circle me-2" aria-hidden="true"></i>Profil saya</a></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger"><i class="ti ti-logout me-2" aria-hidden="true"></i>Keluar</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <main class="page-content" id="konten-utama" tabindex="-1">
        <div class="page-container">
            @yield('content')
        </div>
        <footer class="ng-footer">
            <div>
                <strong>NUTRIGROW</strong> — Pemantauan Pertumbuhan &amp; Gizi Balita Berbasis Komunitas
            </div>
            <div>
                <i class="ti ti-shield-heart" aria-hidden="true"></i> Alat bantu pemantauan standar WHO, bukan pengganti pemeriksaan tenaga kesehatan.
            </div>
        </footer>
    </main>
</div>

<script src="{{ asset('boron/js/vendor.min.js') }}"></script>
<script src="{{ asset('boron/vendor/select2/js/select2.min.js') }}"></script>
<script src="{{ asset('boron/vendor/select2/js/i18n/id.js') }}"></script>
<script src="{{ asset('boron/vendor/flatpickr/flatpickr.min.js') }}"></script>
<script src="{{ asset('boron/vendor/flatpickr/l10n/id.js') }}"></script>
<script src="{{ asset('boron/js/config.js') }}"></script>
<script src="{{ asset('boron/js/app.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof window.flatpickr !== 'undefined') {
            flatpickr.localize(flatpickr.l10ns.id);
            document.querySelectorAll('input[type="date"], .js-datepicker').forEach(function (el) {
                var minDate = el.getAttribute('min') || null;
                var maxDate = el.getAttribute('max') || null;
                var defaultVal = el.value || null;
                flatpickr(el, {
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'j F Y',
                    minDate: minDate,
                    maxDate: maxDate,
                    defaultDate: defaultVal,
                    allowInput: false,
                    disableMobile: true
                });
            });
        }

        if (typeof window.jQuery !== 'undefined' && typeof window.jQuery.fn.select2 !== 'undefined') {
            window.jQuery('select.form-select, select.js-select2').each(function () {
                var $select = window.jQuery(this);
                if ($select.hasClass('select2-hidden-accessible')) return;
                var $modal = $select.closest('.modal');
                var opts = {
                    language: 'id',
                    width: '100%',
                    theme: 'default'
                };
                if ($modal.length) {
                    opts.dropdownParent = $modal;
                }
                $select.select2(opts);
                $select.on('select2:select', function () {
                    if (this.hasAttribute('onchange')) {
                        this.dispatchEvent(new Event('change'));
                    }
                });
            });
        }
    });
</script>
@stack('scripts')
@include('partials.flash-toast')
</body>
</html>
