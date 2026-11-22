@extends('layouts.guest')

@section('title', 'Masuk ke layanan')

@section('content')
    <div class="mb-4">
        <div class="ng-eyebrow">Selamat datang</div>
        <h1 class="h3 mb-1">Masuk ke NUTRIGROW</h1>
        <p class="ng-small ng-muted mb-0">Gunakan akun yang diberikan petugas Posyandu atau Puskesmas.</p>
    </div>

    <form method="POST" action="{{ route('login.store') }}" class="d-grid gap-3" novalidate>
        @csrf
        <div>
            <label class="form-label" for="email">Alamat surel</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}"
                   class="form-control @error('email') is-invalid @enderror"
                   placeholder="nama@contoh.id" autocomplete="email" required autofocus>
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div>
            <label class="form-label" for="password">Kata sandi</label>
            <input type="password" id="password" name="password"
                   class="form-control @error('password') is-invalid @enderror"
                   placeholder="Masukkan kata sandi" autocomplete="current-password" required>
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="d-flex align-items-center justify-content-between">
            <label class="d-flex align-items-center gap-2 ng-small">
                <input type="checkbox" name="remember" value="1" class="form-check-input mt-0">
                Ingat saya di perangkat ini
            </label>
        </div>
        <button type="submit" class="btn btn-primary w-100">
            <i class="ti ti-login me-1" aria-hidden="true"></i> Masuk
        </button>
    </form>

    <div class="ng-divider"></div>

    <div class="mb-2 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <span class="ng-eyebrow mb-0">Coba peran layanan</span>
        <span class="badge badge-soft-secondary">Kata sandi: rahasia123</span>
    </div>
    <p class="ng-tiny ng-muted mb-2">Untuk penilaian dan pelatihan, pilih salah satu peran. Formulir akan terisi otomatis, lalu tekan Masuk.</p>

    <div class="d-grid gap-2">
        @foreach ($demoAccounts as $account)
            <button type="button" class="ng-demo-account js-demo-account"
                    data-email="{{ $account['email'] }}"
                    data-password="rahasia123"
                    aria-label="Isi formulir sebagai {{ $account['role'] }}">
                <span class="ng-avatar ng-avatar-sm" aria-hidden="true">{{ mb_strtoupper(mb_substr($account['name'], 0, 2)) }}</span>
                <span class="flex-grow-1">
                    <strong>{{ $account['role'] }}</strong>
                    <span>{{ $account['description'] }}</span>
                </span>
                <i class="ti ti-arrow-right" aria-hidden="true"></i>
            </button>
        @endforeach
    </div>

    <p class="ng-tiny ng-muted text-center mt-4 mb-0">
        <i class="ti ti-lock" aria-hidden="true"></i> Data balita hanya dapat dilihat oleh petugas dan keluarga yang berwenang.
    </p>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.js-demo-account').forEach((button) => {
        button.addEventListener('click', () => {
            document.getElementById('email').value = button.dataset.email;
            document.getElementById('password').value = button.dataset.password;
            document.getElementById('password').focus();
            window.NG?.showToast('Formulir diisi untuk ' + button.querySelector('strong').textContent + '. Tekan Masuk untuk melanjutkan.');
        });
    });
</script>
@endpush
