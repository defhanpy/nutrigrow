@props(['label', 'value', 'icon' => 'ti-chart-bar', 'tone' => 'mint', 'hint' => null])
<div class="ng-stat ng-tone-{{ $tone }}">
    <div class="ng-stat-top">
        <span class="ng-stat-label">{{ $label }}</span>
        <span class="ng-stat-icon"><i class="ti {{ $icon }}"></i></span>
    </div>
    <div class="ng-stat-value">{{ $value }}</div>
    @if ($hint)
        <div class="ng-stat-hint">{{ $hint }}</div>
    @endif
</div>
