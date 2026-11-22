<?php

namespace App\Http\Controllers\Ortu;

use App\Http\Controllers\Controller;
use App\Services\ChildAccessService;
use App\Services\GrowthChartService;
use App\Services\NutritionService;
use App\Services\WhoGrowthEngineService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly ChildAccessService $access,
        private readonly GrowthChartService $charts,
        private readonly NutritionService $nutrition,
        private readonly WhoGrowthEngineService $growthEngine,
    ) {}

    public function __invoke(Request $request): View
    {
        $children = $this->access->queryFor($request->user())->withCount('measurements')->orderBy('full_name')->get();
        $selected = $children->firstWhere('id', $request->integer('child')) ?? $children->first();
        $assessment = $selected?->latestMeasurement?->assessment;
        $chart = $selected !== null ? $this->charts->weightChart($selected) : null;
        $trajectory = $selected !== null ? $this->growthEngine->evaluateTrajectory($selected) : null;
        $nutrition = $selected !== null ? $this->nutrition->recommendForChild($selected, $assessment) : null;
        $nextVisit = now()->startOfMonth()->addMonth()->day(10);
        $followUps = $selected !== null
            ? $selected->followUps()->where('status', 'pending')->orderBy('target_date')->get()
            : collect();

        return view('ortu.dashboard', [
            'children' => $children,
            'selectedChild' => $selected,
            'assessment' => $assessment,
            'chart' => $chart,
            'trajectory' => $trajectory,
            'nutrition' => $nutrition,
            'followUps' => $followUps,
            'nextVisit' => $nextVisit,
            'measurements' => $selected?->measurements()->with('assessment')->latest('measured_at')->take(6)->get() ?? collect(),
        ]);
    }
}
