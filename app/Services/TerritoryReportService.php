<?php

namespace App\Services;

use App\Enums\RiskStatus;
use App\Models\Child;
use App\Models\FollowUpRecord;
use App\Models\GrowthAssessment;
use App\Models\HealthFacility;
use App\Models\Measurement;
use App\Models\RiskAssessment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TerritoryReportService
{
    public function __construct(private readonly ChildAccessService $access) {}

    /**
     * @return array{
     *     total_target: int,
     *     measured_this_month: int,
     *     coverage_percent: float,
     *     stunting_count: int,
     *     stunting_percent: float,
     *     wasting_count: int,
     *     wasting_percent: float,
     *     open_alerts: int,
     *     follow_up_pending: int
     * }
     */
    public function summary(User $user): array
    {
        $facilityIds = $this->access->puskesmasFacilityIds($user);
        $children = Child::query()->whereIn('facility_id', $facilityIds)->where('is_active', true);
        $total = (clone $children)->count();
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();

        $measured = Measurement::query()
            ->whereIn('child_id', (clone $children)->select('id'))
            ->whereBetween('measured_at', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->distinct('child_id')
            ->count('child_id');

        $latestAssessmentIds = GrowthAssessment::query()
            ->whereIn('child_id', (clone $children)->select('id'))
            ->whereIn('measurement_id', $this->latestMeasurementIdsSubquery((clone $children)->select('id')));

        $stunting = (clone $latestAssessmentIds)
            ->whereIn('lhfa_status', ['Pendek', 'Sangat Pendek'])
            ->count();
        $wasting = (clone $latestAssessmentIds)
            ->whereIn('wfh_status', ['Gizi Kurang (Wasting)', 'Gizi Buruk (Wasting Berat)'])
            ->count();
        $openAlerts = RiskAssessment::query()
            ->whereIn('child_id', (clone $children)->select('id'))
            ->where('status', RiskStatus::Open)
            ->count();
        $pendingFollowUps = FollowUpRecord::query()
            ->whereIn('child_id', (clone $children)->select('id'))
            ->where('status', 'pending')
            ->count();

        return [
            'total_target' => $total,
            'measured_this_month' => $measured,
            'coverage_percent' => $total > 0 ? round($measured / $total * 100, 1) : 0.0,
            'stunting_count' => $stunting,
            'stunting_percent' => $total > 0 ? round($stunting / $total * 100, 1) : 0.0,
            'wasting_count' => $wasting,
            'wasting_percent' => $total > 0 ? round($wasting / $total * 100, 1) : 0.0,
            'open_alerts' => $openAlerts,
            'follow_up_pending' => $pendingFollowUps,
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function posyanduComparison(User $user): Collection
    {
        $facilityIds = $this->access->puskesmasFacilityIds($user);
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();

        return HealthFacility::query()
            ->whereIn('id', $facilityIds)
            ->where('type', 'posyandu')
            ->withCount(['childProfiles as total_target' => fn ($query) => $query->where('is_active', true)])
            ->get()
            ->map(function (HealthFacility $facility) use ($monthStart, $monthEnd): array {
                $childIds = $facility->childProfiles()->where('is_active', true)->pluck('id');
                $measured = Measurement::query()
                    ->whereIn('child_id', $childIds)
                    ->whereBetween('measured_at', [$monthStart->toDateString(), $monthEnd->toDateString()])
                    ->distinct('child_id')
                    ->count('child_id');
                $latestIds = GrowthAssessment::query()
                    ->whereIn('child_id', $childIds)
                    ->whereIn('measurement_id', $this->latestMeasurementIdsSubquery($childIds));
                $stunting = (clone $latestIds)->whereIn('lhfa_status', ['Pendek', 'Sangat Pendek'])->count();
                $wasting = (clone $latestIds)->whereIn('wfh_status', ['Gizi Kurang (Wasting)', 'Gizi Buruk (Wasting Berat)'])->count();
                $openAlerts = RiskAssessment::query()
                    ->whereIn('child_id', $childIds)
                    ->where('status', RiskStatus::Open)
                    ->count();
                $total = $facility->total_target;

                return [
                    'name' => $facility->name,
                    'code' => $facility->code,
                    'total_target' => $total,
                    'measured' => $measured,
                    'coverage_percent' => $total > 0 ? round($measured / $total * 100, 1) : 0.0,
                    'stunting' => $stunting,
                    'wasting' => $wasting,
                    'open_alerts' => $openAlerts,
                ];
            })
            ->sortBy('name')
            ->values();
    }

    /**
     * Ids of each child's most recent measurement, ordered by measurement date.
     *
     * @param  Builder<int, Child>|\Illuminate\Database\Query\Builder  $childIds
     */
    private function latestMeasurementIdsSubquery($childIds): \Illuminate\Database\Query\Builder
    {
        return Measurement::query()
            ->select('measurements.id')
            ->whereIn('measurements.child_id', $childIds)
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('measurements as newer')
                    ->whereColumn('newer.child_id', 'measurements.child_id')
                    ->whereColumn('newer.measured_at', '>', 'measurements.measured_at');
            })
            ->toBase();
    }
}
