@extends('layouts.app')

@section('title', 'Pengguna Wilayah')

@section('content')
    <x-page-head
        eyebrow="{{ auth()->user()->facility?->name ?? 'Puskesmas' }}"
        title="Pengguna wilayah"
        description="Daftar akun tenaga kesehatan, kader Posyandu, dan wali balita yang bertugas di wilayah ini.">
        <x-slot:actions>
            <a href="{{ route('puskesmas.posyandu') }}" class="btn btn-light"><i class="ti ti-building-community me-1"></i> Posyandu</a>
            <a href="{{ route('puskesmas.settings') }}" class="btn btn-primary"><i class="ti ti-settings me-1"></i> Pengaturan</a>
        </x-slot:actions>
    </x-page-head>

    <div class="row g-3 mb-3">
        @foreach ($stats as $stat)
            <div class="col-6 col-xl-3">
                <x-stat-tile :label="$stat['label']" :value="$stat['value']" :icon="$stat['icon']" :tone="$stat['tone']" />
            </div>
        @endforeach
    </div>

    <div class="ng-panel mb-3">
        <form method="GET" action="{{ route('puskesmas.users') }}" class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label" for="role">Peran pengguna</label>
                <select id="role" name="role" class="form-select" onchange="this.form.submit()">
                    <option value="">Semua peran</option>
                    @foreach ($filters['roles'] as $option)
                        <option value="{{ $option->value }}" @selected($filters['role'] === $option->value)>{{ $option->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                @if ($filters['role'] !== '')
                    <a href="{{ route('puskesmas.users') }}" class="btn btn-light"><i class="ti ti-x me-1"></i> Bersihkan filter</a>
                @else
                    <span class="ng-small ng-muted">Menampilkan seluruh akun dalam wilayah kerja.</span>
                @endif
            </div>
        </form>
    </div>

    <div class="ng-panel">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
            <h6 class="ng-panel-title mb-0"><i class="ti ti-users"></i> Daftar akun</h6>
            <span class="badge badge-soft-secondary">{{ $users->total() }} akun</span>
        </div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                <tr>
                    <th style="width: 50px;">No.</th>
                    <th>Nama</th>
                    <th>Peran</th>
                    <th>Fasilitas</th>
                    <th>Status</th>
                    <th>Terhubung dengan</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td class="ng-small text-muted">{{ $users->firstItem() ? $users->firstItem() + $loop->index : $loop->iteration }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="ng-avatar ng-avatar-sm">{{ $user->initials() }}</span>
                                <div>
                                    <div class="fw-bold ng-small">{{ $user->name }}</div>
                                    <div class="ng-tiny ng-muted">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge badge-soft-info">{{ $user->roleLabel() }}</span></td>
                        <td class="ng-small">{{ $user->facility?->name }}</td>
                        <td>
                            <span class="badge {{ $user->status->value === 'active' ? 'ng-badge-good' : 'ng-badge-neutral' }}">{{ $user->status->label() }}</span>
                        </td>
                        <td class="ng-small">
                            @if ($user->isOrangTua())
                                {{ $user->guardianships_count }} balita
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty-state icon="ti-users" title="Tidak ada akun" description="Ubah filter peran untuk melihat akun lain." /></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $users->links() }}</div>
    </div>
@endsection
