@extends('layouts.app')

@section('title', 'Laporan Wilayah')

@section('content')
    <x-page-head eyebrow="Rekap bulanan" title="Laporan wilayah" description="Ringkasan cakupan dan temuan gizi per Posyandu untuk rapat bulanan.">
        <x-slot:actions>
            <a href="{{ route('puskesmas.reports.download') }}" class="btn btn-primary"><i class="ti ti-file-download me-1"></i> Unduh CSV</a>
            <a href="{{ route('puskesmas.dashboard') }}" class="btn btn-light"><i class="ti ti-arrow-left me-1"></i> Dashboard</a>
        </x-slot:actions>
    </x-page-head>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3"><x-stat-tile label="Sasaran balita" :value="$summary['total_target']" icon="ti-users" tone="mint" /></div>
        <div class="col-6 col-xl-3"><x-stat-tile label="Cakupan D/S" :value="$summary['coverage_percent'].'%'" icon="ti-scale" tone="sky" /></div>
        <div class="col-6 col-xl-3"><x-stat-tile label="Stunting" :value="$summary['stunting_percent'].'%'" icon="ti-ruler-measure" tone="amber" /></div>
        <div class="col-6 col-xl-3"><x-stat-tile label="Wasting" :value="$summary['wasting_percent'].'%'" icon="ti-heart-rate-monitor" tone="rose" /></div>
    </div>

    <div class="ng-panel">
        <h6 class="ng-panel-title"><i class="ti ti-table"></i> Rekap per Posyandu</h6>
        <p class="ng-panel-sub">Data agregat bulan {{ now()->translatedFormat('F Y') }}. Tidak memuat identitas anak.</p>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                <tr>
                    <th>Posyandu</th>
                    <th class="text-end">Sasaran</th>
                    <th class="text-end">Ditimbang</th>
                    <th class="text-end">Cakupan</th>
                    <th class="text-end">Pendek</th>
                    <th class="text-end">Wasting</th>
                    <th class="text-end">Perlu ditinjau</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($posyandu as $row)
                    <tr>
                        <td><div class="fw-bold ng-small">{{ $row['name'] }}</div><div class="ng-tiny ng-muted">{{ $row['code'] }}</div></td>
                        <td class="text-end ng-small">{{ $row['total_target'] }}</td>
                        <td class="text-end ng-small">{{ $row['measured'] }}</td>
                        <td class="text-end ng-small fw-bold">{{ $row['coverage_percent'] }}%</td>
                        <td class="text-end ng-small">{{ $row['stunting'] }}</td>
                        <td class="text-end ng-small">{{ $row['wasting'] }}</td>
                        <td class="text-end ng-small">{{ $row['open_alerts'] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
