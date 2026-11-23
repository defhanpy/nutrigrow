<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Child;
use App\Services\ChildAccessService;
use App\Services\GrowthChartService;
use App\Services\NutritionService;
use App\Services\WhoGrowthEngineService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChildController extends Controller
{
    public function __construct(
        private readonly ChildAccessService $access,
        private readonly GrowthChartService $charts,
        private readonly WhoGrowthEngineService $growthEngine,
        private readonly NutritionService $nutrition,
    ) {}

    public function index(Request $request): View
    {
        $children = $this->access->queryFor($request->user())
            ->withCount('measurements')
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.$request->string('q')->trim().'%';
                $query->where(fn ($inner) => $inner
                    ->where('full_name', 'like', $term)
                    ->orWhere('registration_no', 'like', $term));
            })
            ->orderBy('full_name')
            ->paginate(12)
            ->withQueryString();

        return view('shared.children.index', ['children' => $children]);
    }

    public function show(Request $request, Child $child): View
    {
        abort_unless($this->access->canAccess($request->user(), $child), 403);

        $child->load(['facility', 'guardians.user', 'measurements.assessment', 'risks', 'followUps.creator']);
        $assessment = $child->latestMeasurement?->assessment;

        return view('shared.children.show', [
            'child' => $child,
            'chart' => $this->charts->weightChart($child),
            'trajectory' => $this->growthEngine->evaluateTrajectory($child),
            'nutrition' => $this->nutrition->recommendForChild($child, $assessment),
            'assessment' => $assessment,
            'measurements' => $child->measurements()->with('assessment')->latest('measured_at')->paginate(10)->withQueryString(),
        ]);
    }
}
