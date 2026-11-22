<?php

namespace App\Http\Controllers\Puskesmas;

use App\Http\Controllers\Controller;
use App\Models\HealthFacility;
use App\Models\User;
use App\Services\ChildAccessService;
use App\Services\TerritoryReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly TerritoryReportService $reports,
        private readonly ChildAccessService $access,
    ) {}

    public function __invoke(Request $request): View
    {
        $user = $request->user();

        return view('puskesmas.dashboard', [
            'summary' => $this->reports->summary($user),
            'posyandu' => $this->reports->posyanduComparison($user),
            'facilities' => HealthFacility::query()
                ->whereIn('id', $this->access->puskesmasFacilityIds($user))
                ->orderBy('name')
                ->get(),
            'users' => User::query()
                ->whereIn('facility_id', $this->access->puskesmasFacilityIds($user))
                ->with('facility')
                ->orderBy('name')
                ->get(),
        ]);
    }
}
