@extends('layouts.app')

@section('title', 'Analisis Pertumbuhan')

@section('content')
    <x-page-head
        eyebrow="{{ auth()->user()->facility?->name ?? 'Posyandu' }}"
        title="Analisis pertumbuhan"
        description="Lihat sebaran status gizi balita di Posyandu Anda dan temukan anak yang perlu dipantau lebih dekat.">
        <x-slot:actions>
            <a href="{{ route('kader.measurements') }}" class="btn btn-light"><i class="ti ti-scale me-1"></i> Riwayat pengukuran</a>
            <a href="{{ route('follow-ups.index') }}" class="btn btn-primary"><i class="ti ti-clipboard-check me-1"></i> Tindak lanjut</a>
        </x-slot:actions>
    </x-page-head>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <x-stat-tile label="Balita terdaftar" :value="$overview['total']" icon="ti-users" tone="mint" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-tile label="Sudah dinilai" :value="$overview['assessed']" icon="ti-clipboard-data" tone="sky" hint="Memiliki hasil ukur terbaru" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-tile label="Perlu pantauan" :value="$overview['attention_count']" icon="ti-alert-triangle" tone="amber" hint="Status gizi atau tanda risiko" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-tile label="Cakupan bulan ini" :value="$overview['coverage_percent'].'%'" icon="ti-scale" tone="rose" :hint="$overview['measured_this_month'].' balita ditimbang'" />
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-4">
            @include('partials.status-distribution', [
                'title' => 'Berat menurut usia',
                'subtitle' => 'Apakah berat badan anak sesuai usianya.',
                'rows' => $overview['distributions']['wfa'],
                'icon' => 'ti-scale',
            ])
        </div>
        <div class="col-lg-4">
            @include('partials.status-distribution', [
                'title' => 'Tinggi menurut usia',
                'subtitle' => 'Apakah tinggi badan anak sesuai usianya.',
                'rows' => $overview['distributions']['lhfa'],
                'icon' => 'ti-ruler-measure',
            ])
        </div>
        <div class="col-lg-4">
            @include('partials.status-distribution', [
                'title' => 'Berat menurut tinggi',
                'subtitle' => 'Apakah berat badan seimbang dengan tinggi badan.',
                'rows' => $overview['distributions']['wfh'],
                'icon' => 'ti-heart-rate-monitor',
            ])
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-7">
            <div class="ng-panel h-100">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                    <div>
                        <h6 class="ng-panel-title mb-0"><i class="ti ti-user-search"></i> Balita perlu pantauan</h6>
                        <p class="ng-panel-sub mb-0">Diurutkan berdasarkan temuan terbaru.</p>
                    </div>
                    <span class="badge badge-soft-warning">{{ $overview['attention_count'] }} balita</span>
                </div>

                @if ($overview['attention']->isEmpty())
                    <x-empty-state icon="ti-circle-check" title="Semua balita dalam pantauan baik" description="Belum ada status gizi atau tanda risiko yang perlu ditinjau." />
                @else
                    <div class="ng-list">
                        @foreach ($overview['attention'] as $entry)
                            <a href="{{ route('children.show', $entry['child']) }}" class="ng-list-item ng-link-plain">
                                <x-child-avatar :child="$entry['child']" />
                                <div class="flex-grow-1">
                                    <h6>{{ $entry['child']->full_name }}</h6>
                                    <p>{{ $entry['child']->ageLabel() }} · {{ $entry['child']->sex->label() }}</p>
                                    <div class="d-flex flex-wrap gap-1 mt-1">
                                        <x-status-badge :status="$entry['assessment']?->overall_status" />
                                        @if ($entry['open_risks'] > 0)
                                            <span class="badge ng-badge-watch">{{ $entry['open_risks'] }} tanda risiko</span>
                                        @endif
                                    </div>
                                </div>
                                <i class="ti ti-arrow-right" aria-hidden="true"></i>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="col-xl-5">
            <div class="ng-panel ng-panel-mint mb-3">
                <h6 class="ng-panel-title"><i class="ti ti-clipboard-heart"></i> Ringkasan tanda risiko</h6>
                <div class="ng-indicator-row"><span class="ng-indicator-label">Tanda terbuka</span><strong>{{ $risks['open'] }}</strong></div>
                <div class="ng-indicator-row"><span class="ng-indicator-label">Prioritas tinggi</span><strong>{{ $risks['severity']['high'] }}</strong></div>
                <div class="ng-indicator-row"><span class="ng-indicator-label">Perlu tindak lanjut</span><strong>{{ $risks['severity']['medium'] }}</strong></div>
                <div class="ng-indicator-row"><span class="ng-indicator-label">Sudah selesai</span><strong>{{ $risks['resolved'] }}</strong></div>
            </div>

            <div class="ng-panel">
                <h6 class="ng-panel-title"><i class="ti ti-list-details"></i> Tanda yang paling sering muncul</h6>
                @if (empty($risks['flags']))
                    <p class="ng-small ng-muted mb-0">Belum ada tanda risiko terbuka.</p>
                @else
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        @foreach ($risks['flags'] as $flag)
                            <span class="ng-chip">{{ $flag['label'] }} · {{ $flag['count'] }}</span>
                        @endforeach
                    </div>
                @endif
                <div class="ng-divider"></div>
                <p class="ng-small ng-muted mb-0">
                    <i class="ti ti-info-circle"></i> Analisis ini membantu kader memprioritaskan kunjungan, bukan menggantikan penilaian tenaga kesehatan.
                </p>
            </div>
        </div>
    </div>
@endsection
