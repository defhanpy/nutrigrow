@props(['icon' => 'ti-mood-smile', 'title', 'description' => null])
<div class="ng-empty">
    <i class="ti {{ $icon }}"></i>
    <h6>{{ $title }}</h6>
    @if ($description)
        <p>{{ $description }}</p>
    @endif
    {{ $slot }}
</div>
