<?php

namespace App\Http\Controllers\Kader;

use App\Http\Controllers\Controller;
use App\Services\GrowthAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function __construct(private readonly GrowthAnalyticsService $analytics) {}

    public function __invoke(Request $request): View
    {
        $overview = $this->analytics->overview($request->user());

        return view('kader.analytics', [
            'overview' => $overview,
            'risks' => $this->analytics->riskBreakdown($request->user()),
        ]);
    }
}
