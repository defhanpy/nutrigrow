<?php

namespace App\Http\Controllers\Nakes;

use App\Http\Controllers\Controller;
use App\Services\GrowthAnalyticsService;
use App\Services\TerritoryReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function __construct(
        private readonly GrowthAnalyticsService $analytics,
        private readonly TerritoryReportService $reports,
    ) {}

    public function __invoke(Request $request): View
    {
        return view('nakes.analytics', [
            'overview' => $this->analytics->overview($request->user()),
            'risks' => $this->analytics->riskBreakdown($request->user()),
            'posyandu' => $this->reports->posyanduComparison($request->user()),
            'summary' => $this->reports->summary($request->user()),
        ]);
    }
}
