@extends('layouts.app')

@section('title', 'Edukasi Gizi')

@section('content')
    <x-page-head eyebrow="Bahan pangan lokal" title="Edukasi gizi" description="Ide menu sehari-hari dari bahan yang mudah ditemukan di sekitar rumah.">
        <x-slot:actions>
            @if ($children->count() > 1)
                <form method="GET" action="{{ route('nutrition.index') }}">
                    <select name="child" class="form-select" onchange="this.form.submit()">
                        @foreach ($children as $child)
                            <option value="{{ $child->id }}" @selected($selectedChild && $selectedChild->id === $child->id)>{{ $child->full_name }}</option>
                        @endforeach
                    </select>
                </form>
            @endif
        </x-slot:actions>
    </x-page-head>

    @if ($recommendation)
        @include('partials.nutrition-cards', ['recommendation' => $recommendation])
    @endif

    <div class="row g-3 mt-1">
        @foreach ($knowledge as $ageGroup => $entries)
            <div class="col-md-6">
                <div class="ng-panel h-100">
                    <span class="badge badge-soft-info mb-2">{{ \Illuminate\Support\Str::of($ageGroup)->replace('-', '–')->append(' bulan') }}</span>
                    @foreach ($entries as $entry)
                        <div class="mb-3">
                            <h6 class="ng-panel-title mb-1">{{ $entry->title }}</h6>
                            <p class="ng-small mb-2">{{ $entry->content }}</p>
                            <div class="d-flex flex-wrap gap-1">
                                @foreach ($entry->key_nutrients ?? [] as $nutrient)
                                    <span class="ng-chip">{{ $nutrient }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
@endsection
