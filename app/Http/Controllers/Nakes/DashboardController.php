<?php

namespace App\Http\Controllers\Nakes;

use App\Http\Controllers\Controller;
use App\Models\RiskAssessment;
use App\Services\ChildAccessService;
use App\Services\WhoGrowthEngineService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly ChildAccessService $access,
        private readonly WhoGrowthEngineService $growthEngine,
    ) {}

    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $children = $this->access->queryFor($user)->get();
        $childIds = $children->pluck('id');
        $risks = RiskAssessment::query()
            ->with(['child.facility', 'child.latestMeasurement.assessment', 'measurement'])
            ->whereIn('child_id', $childIds)
            ->where('status', 'open')
            ->orderByRaw("case severity when 'high' then 0 when 'medium' then 1 else 2 end")
            ->latest()
            ->get();
        $queue = $risks
            ->groupBy('child_id')
            ->map(function ($group) {
                $ordered = $group->sortBy(fn (RiskAssessment $risk) => match ($risk->severity->value) {
                    'high' => 0,
                    'medium' => 1,
                    default => 2,
                })->values();

                return [
                    'child' => $ordered->first()->child,
                    'worst' => $ordered->first(),
                    'flags' => $ordered,
                ];
            })
            ->sortBy(fn (array $entry) => match ($entry['worst']->severity->value) {
                'high' => 0,
                'medium' => 1,
                default => 2,
            })
            ->values();
        $selectedRisk = $risks->firstWhere('id', $request->integer('risk')) ?? $queue->first()['worst'] ?? null;
        $selectedChild = $selectedRisk?->child
            ?? $children->firstWhere('id', $request->integer('child'))
            ?? $children->first();

        return view('nakes.dashboard', [
            'risks' => $risks,
            'queue' => $queue,
            'selectedRisk' => $selectedRisk,
            'selectedChild' => $selectedChild,
            'stats' => [
                ['label' => 'Peninjauan terbuka', 'value' => $risks->count(), 'icon' => 'ti-alert-triangle', 'tone' => 'amber'],
                ['label' => 'Prioritas tinggi', 'value' => $risks->where('severity.value', 'high')->count(), 'icon' => 'ti-heart-rate-monitor', 'tone' => 'rose'],
                ['label' => 'Balita terpantau', 'value' => $children->count(), 'icon' => 'ti-users', 'tone' => 'mint'],
                ['label' => 'Sudah ditinjau', 'value' => RiskAssessment::query()->whereIn('child_id', $childIds)->where('status', 'resolved')->count(), 'icon' => 'ti-circle-check', 'tone' => 'sky'],
            ],
            'trajectory' => $selectedChild !== null ? $this->growthEngine->evaluateTrajectory($selectedChild) : null,
        ]);
    }
}
