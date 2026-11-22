@props(['recommendation'])
<div class="ng-panel ng-panel-mint">
    <div class="d-flex align-items-start justify-content-between gap-2 mb-1">
        <div>
            <h6 class="ng-panel-title mb-1"><i class="ti ti-salad"></i> {{ $recommendation['headline'] }}</h6>
            <p class="ng-panel-sub mb-0">{{ $recommendation['summary'] }}</p>
        </div>
        <span class="badge badge-soft-success ng-nowrap">{{ $recommendation['age_label'] }}</span>
    </div>

    <div class="d-flex flex-wrap gap-1 my-2">
        @foreach ($recommendation['nutrients'] as $nutrient)
            <span class="ng-chip">{{ $nutrient }}</span>
        @endforeach
    </div>

    <div class="row g-2 mt-1">
        @foreach (array_slice($recommendation['foods'], 0, 4) as $food)
            <div class="col-6 col-xl-3">
                <div class="ng-food-card">
                    <span class="ng-food-emoji">{{ $food['emoji'] }}</span>
                    <h6>{{ $food['name'] }}</h6>
                    <p>{{ $food['why'] }}</p>
                    <div class="ng-food-serving"><i class="ti ti-bowl-spoon"></i> {{ $food['texture'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <ul class="mt-3 mb-0 ps-3 ng-small">
        @foreach (array_slice($recommendation['tips'], 0, 3) as $tip)
            <li class="mb-1">{{ $tip }}</li>
        @endforeach
    </ul>
    <p class="ng-tiny ng-muted mt-2 mb-0"><i class="ti ti-info-circle"></i> Saran menu bersifat edukatif dan bukan resep terapi.</p>
</div>
