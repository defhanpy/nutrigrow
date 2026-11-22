@extends('layouts.guest')

@section('title', 'Masuk ke layanan')

@section('content')
    <div class="mb-4">
        <div class="ng-eyebrow">Selamat datang</div>
        <h2 class="h3 mb-1">Masuk ke NUTRIGROW</h2>
        <p class="ng-small ng-muted mb-0">Gunakan akun yang diberikan petugas Posyandu atau Puskesmas.</p>
    </div>

    <form method="POST" action="{{ route('login.store') }}" class="d-grid gap-3">
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
            <i class="ti ti-login me-1"></i> Masuk
        </button>
    </form>

    <div class="ng-divider"></div>

    <div class="mb-2 d-flex align-items-center justify-content-between">
        <span class="ng-eyebrow mb-0">Akun demo untuk juri</span>
        <span class="badge badge-soft-secondary">Kata sandi: rahasia123</span>
    </div>
    <p class="ng-tiny ng-muted mb-2">Pilih salah satu peran untuk mengisi formulir secara otomatis.</p>

    <div class="d-grid gap-2">
        @foreach ($demoAccounts as $account)
            <button type="button" class="ng-demo-account js-demo-account"
                    data-email="{{ $account['email'] }}"
                    data-password="rahasia123">
                <span class="ng-avatar ng-avatar-sm">{{ mb_strtoupper(mb_substr($account['name'], 0, 2)) }}</span>
                <span class="flex-grow-1">
                    <strong>{{ $account['role'] }}</strong>
                    <span>{{ $account['description'] }}</span>
                </span>
                <i class="ti ti-arrow-right"></i>
            </button>
        @endforeach
    </div>

    <p class="ng-tiny ng-muted text-center mt-4 mb-0">
        <i class="ti ti-lock"></i> Data balita hanya dapat dilihat oleh petugas dan keluarga yang berwenang.
    </p>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.js-demo-account').forEach((button) => {
        button.addEventListener('click', () => {
            document.getElementById('email').value = button.dataset.email;
            document.getElementById('password').value = button.dataset.password;
            document.getElementById('password').focus();
        });
    });
</script>
@endpush
