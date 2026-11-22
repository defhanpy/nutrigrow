@extends('layouts.app')

@section('title', 'Profil Saya')

@section('content')
    <x-page-head eyebrow="Akun layanan" title="Profil saya" description="Informasi akun dan fasilitas tempat Anda bertugas." />

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="ng-panel">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <span class="ng-avatar ng-avatar-lg">{{ $user->initials() }}</span>
                    <div>
                        <h5 class="mb-1">{{ $user->name }}</h5>
                        <span class="badge badge-soft-success">{{ $user->roleLabel() }}</span>
                    </div>
                </div>
                <div class="ng-indicator-row"><span class="ng-indicator-label">Alamat surel</span><strong class="ng-small">{{ $user->email }}</strong></div>
                <div class="ng-indicator-row"><span class="ng-indicator-label">Fasilitas</span><strong class="ng-small">{{ $user->facility?->name ?? '—' }}</strong></div>
                <div class="ng-indicator-row"><span class="ng-indicator-label">Jenis fasilitas</span><strong class="ng-small">{{ $user->facility?->type->label() ?? '—' }}</strong></div>
                <div class="ng-indicator-row"><span class="ng-indicator-label">Status akun</span><strong class="ng-small">{{ $user->status->label() }}</strong></div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="ng-panel ng-panel-mint h-100">
                <h6 class="ng-panel-title"><i class="ti ti-shield-heart"></i> Menjaga data keluarga</h6>
                <p class="ng-small">
                    Data balita adalah data pribadi keluarga. Mohon hanya membuka profil anak yang menjadi tanggung jawab Anda dan tidak membagikan tangkapan layar yang memuat identitas anak.
                </p>
                <ul class="ng-small ps-3 mb-0">
                    <li class="mb-1">Keluar dari akun setelah menggunakan perangkat bersama.</li>
                    <li class="mb-1">Laporkan segera bila ada data yang tidak sesuai.</li>
                    <li>Gunakan kata sandi yang tidak dibagikan ke orang lain.</li>
                </ul>
            </div>
        </div>
    </div>
@endsection
