@props(['child', 'size' => ''])
@php
    $initial = mb_strtoupper(mb_substr($child->full_name, 0, 1));
    $tone = $child->sex === \App\Enums\Sex::LakiLaki ? 'sky' : 'rose';
@endphp
<span class="ng-avatar {{ $size === 'lg' ? 'ng-avatar-lg' : '' }} ng-tone-{{ $tone }}" title="{{ $child->full_name }}">{{ $initial }}</span>
