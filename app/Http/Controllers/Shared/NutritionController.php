<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\NutritionKnowledge;
use App\Services\ChildAccessService;
use App\Services\NutritionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NutritionController extends Controller
{
    public function __construct(
        private readonly ChildAccessService $access,
        private readonly NutritionService $nutrition,
    ) {}

    public function index(Request $request): View
    {
        $children = $this->access->queryFor($request->user())->orderBy('full_name')->get();
        $selected = $children->firstWhere('id', $request->integer('child')) ?? $children->first();
        $recommendation = $selected !== null
            ? $this->nutrition->recommendForChild($selected, $selected->latestMeasurement?->assessment)
            : null;

        return view('shared.nutrition.index', [
            'children' => $children,
            'selectedChild' => $selected,
            'recommendation' => $recommendation,
            'knowledge' => NutritionKnowledge::query()->orderBy('age_group')->get()->groupBy('age_group'),
        ]);
    }
}
