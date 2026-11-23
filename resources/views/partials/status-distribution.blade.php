@props(['title', 'subtitle' => null, 'rows', 'icon' => 'ti-chart-bar'])
<div class="ng-panel h-100">
    <h6 class="ng-panel-title"><i class="ti {{ $icon }}"></i> {{ $title }}</h6>
    @if ($subtitle)
        <p class="ng-panel-sub">{{ $subtitle }}</p>
    @endif
    <div class="ng-distribution">
        @foreach ($rows as $row)
            <div class="ng-distribution-row">
                <div class="d-flex align-items-center justify-content-between gap-2">
                    <span class="ng-small fw-bold">{{ $row['label'] }}</span>
                    <span class="ng-small ng-muted">{{ $row['count'] }} balita · {{ $row['percent'] }}%</span>
                </div>
                <div class="ng-distribution-track" role="img" aria-label="{{ $row['label'] }}: {{ $row['count'] }} balita ({{ $row['percent'] }}%)">
                    <div class="ng-distribution-fill ng-fill-{{ $row['tone'] }}" style="width: {{ max(0, min(100, $row['percent'])) }}%;"></div>
                </div>
            </div>
        @endforeach
    </div>
</div>
