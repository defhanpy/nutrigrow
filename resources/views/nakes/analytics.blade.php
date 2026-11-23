@extends('layouts.app')

@section('title', 'Hasil Analisis')

@section('content')
    <x-page-head
        eyebrow="{{ auth()->user()->facility?->name ?? 'Puskesmas' }}"
        title="Hasil analisis wilayah"
        description="Telaah status gizi dan tanda risiko di seluruh Posyandu dalam wilayah kerja Puskesmas Sukamaju.">
        <x-slot:actions>
            <a href="{{ route('nakes.reviews') }}" class="btn btn-light"><i class="ti ti-alert-triangle me-1"></i> Daftar peninjauan</a>
            <a href="{{ route('children.index') }}" class="btn btn-primary"><i class="ti ti-users me-1"></i> Profil anak</a>
        </x-slot:actions>
    </x-page-head>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <x-stat-tile label="Balita terpantau" :value="$overview['total']" icon="ti-users" tone="mint" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-tile label="Perlu pantauan" :value="$overview['attention_count']" icon="ti-alert-triangle" tone="amber" hint="Status gizi atau tanda risiko" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-tile label="Prevalensi stunting" :value="$summary['stunting_percent'].'%'" icon="ti-ruler-measure" tone="rose" :hint="$summary['stunting_count'].' balita pendek'" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-tile label="Prevalensi wasting" :value="$summary['wasting_percent'].'%'" icon="ti-heart-rate-monitor" tone="sky" :hint="$summary['wasting_count'].' balita wasting'" />
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-4">
            @include('partials.status-distribution', [
                'title' => 'Berat menurut usia',
                'subtitle' => 'Sebaran status berat badan seluruh balita terpantau.',
                'rows' => $overview['distributions']['wfa'],
                'icon' => 'ti-scale',
            ])
        </div>
        <div class="col-lg-4">
            @include('partials.status-distribution', [
                'title' => 'Tinggi menurut usia',
                'subtitle' => 'Gambaran kejadian pendek dan sangat pendek.',
                'rows' => $overview['distributions']['lhfa'],
                'icon' => 'ti-ruler-measure',
            ])
        </div>
        <div class="col-lg-4">
            @include('partials.status-distribution', [
                'title' => 'Berat menurut tinggi',
                'subtitle' => 'Gambaran wasting dan risiko gizi lebih.',
                'rows' => $overview['distributions']['wfh'],
                'icon' => 'ti-heart-rate-monitor',
            ])
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-7">
            <div class="ng-panel">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                    <div>
                        <h6 class="ng-panel-title mb-0"><i class="ti ti-building-community"></i> Telaah per Posyandu</h6>
                        <p class="ng-panel-sub mb-0">Temuan gizi dan tanda risiko terbuka tiap Posyandu.</p>
                    </div>
                    <span class="badge badge-soft-secondary">{{ $posyandu->count() }} Posyandu</span>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                        <tr>
                            <th style="width: 45px;">No.</th>
                            <th>Posyandu</th>
                            <th class="text-end">Sasaran</th>
                            <th class="text-end">Pendek</th>
                            <th class="text-end">Wasting</th>
                            <th class="text-end">Perlu ditinjau</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($posyandu as $row)
                            <tr>
                                <td class="ng-small text-muted">{{ $loop->iteration }}</td>
                                <td>
                                    <div class="fw-bold ng-small">{{ $row['name'] }}</div>
                                    <div class="ng-tiny ng-muted">{{ $row['code'] }}</div>
                                </td>
                                <td class="text-end ng-small">{{ $row['total_target'] }}</td>
                                <td class="text-end ng-small">{{ $row['stunting'] }}</td>
                                <td class="text-end ng-small">{{ $row['wasting'] }}</td>
                                <td class="text-end"><span class="badge {{ $row['open_alerts'] > 0 ? 'ng-badge-watch' : 'ng-badge-good' }}">{{ $row['open_alerts'] }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="ng-small ng-muted text-center py-3">Belum ada Posyandu pada wilayah ini.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="ng-panel ng-panel-amber mb-3">
                <h6 class="ng-panel-title"><i class="ti ti-stethoscope"></i> Telaah klinis</h6>
                <div class="ng-indicator-row"><span class="ng-indicator-label">Tanda terbuka</span><strong>{{ $risks['open'] }}</strong></div>
                <div class="ng-indicator-row"><span class="ng-indicator-label">Prioritas tinggi</span><strong>{{ $risks['severity']['high'] }}</strong></div>
                <div class="ng-indicator-row"><span class="ng-indicator-label">Perlu tindak lanjut</span><strong>{{ $risks['severity']['medium'] }}</strong></div>
                <div class="ng-indicator-row"><span class="ng-indicator-label">Sudah ditinjau</span><strong>{{ $risks['resolved'] }}</strong></div>
                <div class="ng-indicator-row"><span class="ng-indicator-label">Ditutup</span><strong>{{ $risks['dismissed'] }}</strong></div>
            </div>

            <div class="ng-panel">
                <h6 class="ng-panel-title"><i class="ti ti-list-details"></i> Temuan paling sering</h6>
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
                    <i class="ti ti-shield-heart"></i> Data ini bersifat agregat untuk telaah program. Keputusan klinis tetap mengikuti pemeriksaan langsung.
                </p>
            </div>
        </div>
    </div>
@endsection
