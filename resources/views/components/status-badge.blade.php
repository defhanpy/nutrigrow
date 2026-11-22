@props(['status' => null])
@php
    $value = $status ?? 'Belum dinilai';
    $tone = match (true) {
        in_array($value, ['Gizi Baik', 'Normal', 'Tinggi'], true) => 'good',
        in_array($value, ['Berisiko Gizi Lebih', 'Gizi Lebih'], true) => 'watch',
        in_array($value, ['Gizi Kurang', 'Pendek'], true) => 'alert',
        in_array($value, ['Gizi Buruk', 'Sangat Pendek', 'Gizi Buruk (Wasting Berat)', 'Gizi Kurang (Wasting)'], true) => 'alert',
        default => 'neutral',
    };
@endphp
<span {{ $attributes->class(['badge', 'ng-badge-'.$tone]) }}>{{ $value }}</span>
