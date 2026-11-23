@extends('layouts.app')

@section('title', 'Posyandu Binaan')

@section('content')
    <x-page-head
        eyebrow="{{ auth()->user()->facility?->name ?? 'Puskesmas' }}"
        title="Direktori Posyandu"
        description="Data sasaran balita, kader aktif, dan jadwal penimbangan setiap Posyandu binaan.">
        <x-slot:actions>
            <a href="{{ route('puskesmas.users') }}" class="btn btn-light"><i class="ti ti-user-cog me-1"></i> Pengguna</a>
            <a href="{{ route('puskesmas.reports') }}" class="btn btn-primary"><i class="ti ti-file-download me-1"></i> Laporan wilayah</a>
        </x-slot:actions>
    </x-page-head>

    <div class="row g-3 mb-3">
        @foreach ($stats as $stat)
            <div class="col-6 col-xl-3">
                <x-stat-tile :label="$stat['label']" :value="$stat['value']" :icon="$stat['icon']" :tone="$stat['tone']" />
            </div>
        @endforeach
    </div>

    <div class="row g-3">
        @forelse ($posyandu as $entry)
            <div class="col-md-6 col-xl-4">
                <div class="ng-panel h-100">
                    <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                        <div>
                            <h6 class="ng-panel-title mb-1">{{ $entry['facility']->name }}</h6>
                            <p class="ng-panel-sub mb-0">{{ $entry['facility']->code }} · {{ $entry['facility']->address }}</p>
                        </div>
                        <span class="ng-list-icon"><i class="ti ti-building-community"></i></span>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <div class="ng-soft-box text-center">
                                <div class="ng-stat-value" style="font-size: 26px;">{{ $entry['total_target'] }}</div>
                                <div class="ng-tiny ng-muted">Sasaran balita</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="ng-soft-box text-center">
                                <div class="ng-stat-value" style="font-size: 26px;">{{ $entry['active_kader'] }}</div>
                                <div class="ng-tiny ng-muted">Kader aktif</div>
                            </div>
                        </div>
                    </div>

                    <div class="ng-indicator-row"><span class="ng-indicator-label">Ditimbang bulan ini</span><strong>{{ $entry['measured_this_month'] }} balita</strong></div>
                    <div class="ng-indicator-row"><span class="ng-indicator-label">Jadwal penimbangan</span><strong class="ng-small">{{ $entry['schedule'] }}</strong></div>
                    <div class="ng-indicator-row">
                        <span class="ng-indicator-label">Cakupan bulan ini</span>
                        <span class="badge {{ $entry['coverage_percent'] >= 80 ? 'ng-badge-good' : ($entry['coverage_percent'] >= 50 ? 'ng-badge-watch' : 'ng-badge-alert') }}">{{ $entry['coverage_percent'] }}%</span>
                    </div>

                    <div class="ng-distribution-track mt-2" role="img" aria-label="Cakupan penimbangan {{ $entry['facility']->name }}: {{ $entry['coverage_percent'] }}%">
                        <div class="ng-distribution-fill ng-fill-good" style="width: {{ max(0, min(100, $entry['coverage_percent'])) }}%;"></div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <x-empty-state icon="ti-building-community" title="Belum ada Posyandu" description="Posyandu binaan pada wilayah ini akan tampil di sini." />
            </div>
        @endforelse
    </div>
@endsection
