@props(['severity'])
@php
    $value = $severity instanceof \App\Enums\RiskSeverity ? $severity->value : $severity;
    $tone = match ($value) {
        'high' => 'alert',
        'medium' => 'watch',
        default => 'info',
    };
@endphp
<span {{ $attributes->class(['badge', 'ng-badge-'.$tone]) }}>{{ $severity instanceof \App\Enums\RiskSeverity ? $severity->label() : ucfirst($value) }}</span>
