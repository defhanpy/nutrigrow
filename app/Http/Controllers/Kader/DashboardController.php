<?php

namespace App\Http\Controllers\Kader;

use App\Http\Controllers\Controller;
use App\Models\Child;
use App\Models\FollowUpRecord;
use App\Models\Measurement;
use App\Services\ChildAccessService;
use App\Services\GrowthChartService;
use App\Services\GrowthMonitoringService;
use App\Services\NutritionService;
use App\Services\WhoGrowthEngineService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly ChildAccessService $access,
        private readonly GrowthChartService $charts,
        private readonly WhoGrowthEngineService $growthEngine,
        private readonly NutritionService $nutrition,
        private readonly GrowthMonitoringService $monitoring,
    ) {}

    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $children = $this->access->queryFor($user)->orderBy('full_name')->get();
        $selected = $this->resolveSelectedChild($request, $children);
        $chart = $selected !== null ? $this->charts->weightChart($selected) : null;
        $trajectory = $selected !== null ? $this->growthEngine->evaluateTrajectory($selected) : null;
        $assessment = $selected?->latestMeasurement?->assessment;
        $nutrition = $selected !== null ? $this->nutrition->recommendForChild($selected, $assessment) : null;
        $followUps = $selected !== null
            ? $selected->followUps()->with('creator')->latest('target_date')->take(6)->get()
            : collect();
        $today = now()->toDateString();
        $measuredToday = Measurement::query()
            ->whereIn('child_id', $children->pluck('id'))
            ->whereDate('measured_at', $today)
            ->count();
        $needReview = $children->filter(
            fn (Child $child) => $child->latestMeasurement?->assessment?->hasConcern() ?? false,
        )->count();
        $pendingFollowUps = FollowUpRecord::query()
            ->whereIn('child_id', $children->pluck('id'))
            ->where('status', 'pending')
            ->count();

        return view('kader.dashboard', [
            'children' => $children,
            'selectedChild' => $selected,
            'chart' => $chart,
            'trajectory' => $trajectory,
            'nutrition' => $nutrition,
            'followUps' => $followUps,
            'stats' => [
                ['label' => 'Balita terdaftar', 'value' => $children->count(), 'icon' => 'ti-users', 'tone' => 'mint'],
                ['label' => 'Ditimbang hari ini', 'value' => $measuredToday, 'icon' => 'ti-scale', 'tone' => 'sky'],
                ['label' => 'Perlu ditinjau', 'value' => $needReview, 'icon' => 'ti-alert-triangle', 'tone' => 'amber'],
                ['label' => 'Tindak lanjut aktif', 'value' => $pendingFollowUps, 'icon' => 'ti-clipboard-check', 'tone' => 'rose'],
            ],
            'recentMeasurements' => Measurement::query()
                ->with(['child', 'assessment'])
                ->whereIn('child_id', $children->pluck('id'))
                ->latest('measured_at')
                ->take(8)
                ->get(),
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Child>  $children
     */
    private function resolveSelectedChild(Request $request, $children): ?Child
    {
        $selectedId = $request->integer('child');

        if ($selectedId > 0) {
            return $children->firstWhere('id', $selectedId);
        }

        return $children->first();
    }
}
