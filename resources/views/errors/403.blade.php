@extends('layouts.app')

@section('title', '403 Akses Dibatasi')

@section('content')
<div class="ng-panel text-center my-4 py-5" style="max-width: 600px; margin-left: auto; margin-right: auto;">
    <div class="ng-avatar ng-avatar-lg mx-auto mb-3" style="width: 64px; height: 64px; font-size: 28px; background: #fff0f0; border: 2px solid var(--ng-ink); box-shadow: 3px 3px 0 var(--ng-ink);">
        <i class="ti ti-shield-x text-danger"></i>
    </div>
    <h3 class="fw-bold mb-2">Akses Dibatasi</h3>
    <p class="text-muted ng-small mx-auto mb-4" style="max-width: 44ch;">
        {{ $exception->getMessage() ?: 'Halaman ini tidak tersedia untuk peran akun Anda saat ini.' }}
    </p>
    <div class="d-flex justify-content-center gap-2">
        @auth
            <a href="{{ route(auth()->user()->homeRoute()) }}" class="btn btn-primary">
                <i class="ti ti-home me-1"></i> Kembali ke Beranda
            </a>
            <form method="POST" action="{{ route('logout') }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-dark">
                    <i class="ti ti-logout me-1"></i> Keluar Akun
                </button>
            </form>
        @else
            <a href="{{ route('login') }}" class="btn btn-primary">
                <i class="ti ti-login me-1"></i> Masuk ke Sistem
            </a>
        @endauth
    </div>
</div>
@endsection
