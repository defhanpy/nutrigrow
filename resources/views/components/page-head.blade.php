@props(['eyebrow' => null, 'title', 'description' => null])
<div class="ng-page-head">
    <div>
        @if ($eyebrow)
            <div class="ng-eyebrow">{{ $eyebrow }}</div>
        @endif
        <h1>{{ $title }}</h1>
        @if ($description)
            <p>{{ $description }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="d-flex flex-wrap gap-2">{{ $actions }}</div>
    @endisset
</div>
