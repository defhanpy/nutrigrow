@extends('layouts.app')

@section('title', 'Riwayat Pengukuran')

@section('content')
    <x-page-head
        eyebrow="{{ auth()->user()->facility?->name ?? 'Posyandu' }}"
        title="Riwayat pengukuran"
        description="Catat hasil penimbangan baru dan telusuri kembali kunjungan balita di Posyandu Anda.">
        <x-slot:actions>
            @if ($selectedChild)
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#measurementModal">
                    <i class="ti ti-scale me-1"></i> Ukur {{ $selectedChild->full_name }}
                </button>
            @endif
            <a href="{{ route('kader.analytics') }}" class="btn btn-light"><i class="ti ti-chart-line me-1"></i> Analisis pertumbuhan</a>
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
        <form method="GET" action="{{ route('kader.measurements') }}" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label" for="child">Pilih balita</label>
                <select id="child" name="child" class="form-select" onchange="this.form.submit()">
                    <option value="">Semua balita</option>
                    @foreach ($children as $child)
                        <option value="{{ $child->id }}" @selected($selectedChild && $selectedChild->id === $child->id)>
                            {{ $child->full_name }} · {{ $child->ageLabel() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="month">Bulan</label>
                <input type="month" id="month" name="month" class="form-control" value="{{ request('month') }}">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100"><i class="ti ti-filter me-1"></i> Terapkan</button>
                @if (request()->filled('child') || request()->filled('month'))
                    <a href="{{ route('kader.measurements') }}" class="btn btn-light" aria-label="Bersihkan filter"><i class="ti ti-x"></i></a>
                @endif
            </div>
        </form>
    </div>

    <div class="ng-panel">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
            <div>
                <h6 class="ng-panel-title mb-0"><i class="ti ti-history"></i> Catatan penimbangan</h6>
                <p class="ng-panel-sub mb-0">
                    {{ $selectedChild ? 'Riwayat '.$selectedChild->full_name : 'Seluruh balita di Posyandu ini' }}
                </p>
            </div>
            <span class="badge badge-soft-secondary">{{ $measurements->total() }} catatan</span>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Balita</th>
                    <th>Usia saat ukur</th>
                    <th>Berat</th>
                    <th>Tinggi</th>
                    <th>Posisi</th>
                    <th>Penilaian</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($measurements as $measurement)
                    <tr>
                        <td class="ng-small ng-nowrap">{{ $measurement->measured_at->translatedFormat('d M Y') }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <x-child-avatar :child="$measurement->child" />
                                <div>
                                    <div class="fw-bold ng-small">{{ $measurement->child->full_name }}</div>
                                    <div class="ng-tiny ng-muted">{{ $measurement->child->registration_no }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="ng-small">{{ $measurement->assessment?->age_in_days ? floor($measurement->assessment->age_in_days / 30.4375).' bulan' : '—' }}</td>
                        <td class="ng-small">{{ $measurement->weight_kg }} kg</td>
                        <td class="ng-small">{{ $measurement->height_cm }} cm</td>
                        <td class="ng-small">{{ $measurement->method->label() }}</td>
                        <td><x-status-badge :status="$measurement->assessment?->overall_status" /></td>
                        <td class="text-end">
                            <a href="{{ route('children.show', $measurement->child) }}" class="btn btn-outline-primary btn-sm" aria-label="Buka profil {{ $measurement->child->full_name }}">
                                <i class="ti ti-arrow-right"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <x-empty-state icon="ti-scale" title="Belum ada pengukuran" description="Catat penimbangan pertama lewat tombol ukur di atas." />
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $measurements->links() }}</div>
    </div>

    @if ($selectedChild)
        @include('partials.measurement-modal')
    @endif
@endsection
