@props(['trajectory'])
<div class="ng-panel">
    <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
        <div>
            <h6 class="ng-panel-title mb-0"><i class="ti ti-activity"></i> Membaca pola pertumbuhan</h6>
            <p class="ng-panel-sub mb-0">{{ $trajectory['summary'] }}</p>
        </div>
        <span class="badge badge-soft-secondary ng-nowrap">{{ $trajectory['visits_analyzed'] }} kunjungan</span>
    </div>

    @if (empty($trajectory['findings']))
        <div class="ng-soft-box ng-small">
            <i class="ti ti-circle-check text-success"></i>
            Tidak ada pola yang perlu ditinjau khusus dari riwayat kunjungan saat ini.
        </div>
    @else
        <div class="ng-list">
            @foreach ($trajectory['findings'] as $finding)
                <div class="ng-list-item">
                    <span class="ng-list-icon ng-tone-{{ $finding['severity'] === 'medium' ? 'amber' : 'sky' }}">
                        <i class="ti {{ $finding['severity'] === 'medium' ? 'ti-alert-triangle' : 'ti-info-circle' }}"></i>
                    </span>
                    <div>
                        <h6>{{ $finding['title'] }}</h6>
                        <p>{{ $finding['description'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
