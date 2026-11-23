<?php

namespace App\Services;

use App\Enums\RiskStatus;
use App\Models\Child;
use App\Models\GrowthAssessment;
use App\Models\Measurement;
use App\Models\RiskAssessment;
use App\Models\User;
use Illuminate\Support\Collection;

class GrowthAnalyticsService
{
    /**
     * Canonical display order for every growth indicator.
     *
     * @var array<string, array<int, string>>
     */
    private const INDICATOR_STATUSES = [
        'wfa' => ['Gizi Buruk', 'Gizi Kurang', 'Gizi Baik', 'Berisiko Gizi Lebih', 'Gizi Lebih'],
        'lhfa' => ['Sangat Pendek', 'Pendek', 'Normal', 'Tinggi'],
        'wfh' => ['Gizi Buruk (Wasting Berat)', 'Gizi Kurang (Wasting)', 'Normal', 'Berisiko Gizi Lebih', 'Gizi Lebih'],
    ];

    public function __construct(private readonly ChildAccessService $access) {}

    /**
     * Children visible to the signed-in user with their latest growth assessment.
     *
     * @return Collection<int, Child>
     */
    public function childrenFor(User $user): Collection
    {
        return $this->access->queryFor($user)
            ->with(['latestMeasurement.assessment'])
            ->orderBy('full_name')
            ->get();
    }

    /**
     * Aggregated growth picture for one Posyandu or Puskesmas territory.
     *
     * @return array{
     *     total: int,
     *     assessed: int,
     *     measured_this_month: int,
     *     coverage_percent: float,
     *     distributions: array<string, array<int, array{label: string, count: int, percent: float, tone: string}>>,
     *     attention: Collection<int, array{child: Child, assessment: GrowthAssessment|null, open_risks: int}>,
     *     attention_count: int
     * }
     */
    public function overview(User $user): array
    {
        $children = $this->childrenFor($user);
        $childIds = $children->pluck('id');
        $assessments = $children
            ->map(fn (Child $child): ?GrowthAssessment => $child->latestMeasurement?->assessment)
            ->filter()
            ->values();
        $measuredThisMonth = Measurement::query()
            ->whereIn('child_id', $childIds)
            ->whereBetween('measured_at', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->distinct('child_id')
            ->count('child_id');
        $openRiskCounts = RiskAssessment::query()
            ->whereIn('child_id', $childIds)
            ->where('status', RiskStatus::Open)
            ->selectRaw('child_id, count(*) as aggregate')
            ->groupBy('child_id')
            ->pluck('aggregate', 'child_id');
        $attention = $children
            ->map(fn (Child $child): array => [
                'child' => $child,
                'assessment' => $child->latestMeasurement?->assessment,
                'open_risks' => (int) ($openRiskCounts[$child->id] ?? 0),
            ])
            ->filter(fn (array $entry): bool => ($entry['assessment']?->hasConcern() ?? false) || $entry['open_risks'] > 0)
            ->values();

        return [
            'total' => $children->count(),
            'assessed' => $assessments->count(),
            'measured_this_month' => $measuredThisMonth,
            'coverage_percent' => $children->count() > 0
                ? round($measuredThisMonth / $children->count() * 100, 1)
                : 0.0,
            'distributions' => [
                'wfa' => $this->distribution($assessments, 'wfa'),
                'lhfa' => $this->distribution($assessments, 'lhfa'),
                'wfh' => $this->distribution($assessments, 'wfh'),
            ],
            'attention' => $attention,
            'attention_count' => $attention->count(),
        ];
    }

    /**
     * Open, resolved, and dismissed risk counts for the territory.
     *
     * @return array{
     *     total: int,
     *     open: int,
     *     resolved: int,
     *     dismissed: int,
     *     severity: array{high: int, medium: int, low: int},
     *     flags: array<int, array{label: string, count: int}>
     * }
     */
    public function riskBreakdown(User $user): array
    {
        $childIds = $this->access->queryFor($user)->pluck('id');
        $risks = RiskAssessment::query()->whereIn('child_id', $childIds)->get();
        $open = $risks->where('status', RiskStatus::Open);

        return [
            'total' => $risks->count(),
            'open' => $open->count(),
            'resolved' => $risks->where('status', RiskStatus::Resolved)->count(),
            'dismissed' => $risks->where('status', RiskStatus::Dismissed)->count(),
            'severity' => [
                'high' => $open->where('severity.value', 'high')->count(),
                'medium' => $open->where('severity.value', 'medium')->count(),
                'low' => $open->where('severity.value', 'low')->count(),
            ],
            'flags' => $open
                ->groupBy('flag')
                ->map->count()
                ->sortDesc()
                ->map(fn (int $count, string $flag): array => [
                    'label' => $this->flagLabel($flag),
                    'count' => $count,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  Collection<int, GrowthAssessment>  $assessments
     * @return array<int, array{label: string, count: int, percent: float, tone: string}>
     */
    private function distribution(Collection $assessments, string $indicator): array
    {
        $attribute = match ($indicator) {
            'wfa' => 'wfa_status',
            'lhfa' => 'lhfa_status',
            default => 'wfh_status',
        };
        $counts = $assessments->countBy(fn (GrowthAssessment $assessment): ?string => $assessment->{$attribute});
        $total = max(1, $assessments->count());

        return array_map(fn (string $status): array => [
            'label' => $status,
            'count' => $counts[$status] ?? 0,
            'percent' => round((($counts[$status] ?? 0) / $total) * 100, 1),
            'tone' => $this->toneFor($status),
        ], self::INDICATOR_STATUSES[$indicator]);
    }

    private function toneFor(string $status): string
    {
        return match (true) {
            in_array($status, ['Gizi Baik', 'Normal', 'Tinggi'], true) => 'good',
            in_array($status, ['Berisiko Gizi Lebih', 'Gizi Lebih'], true) => 'watch',
            in_array($status, ['Gizi Kurang', 'Pendek'], true) => 'alert',
            in_array($status, ['Gizi Buruk', 'Sangat Pendek', 'Gizi Buruk (Wasting Berat)', 'Gizi Kurang (Wasting)'], true) => 'alert',
            default => 'neutral',
        };
    }

    private function flagLabel(string $flag): string
    {
        return match ($flag) {
            'underweight_severe' => 'Berat badan sangat kurang',
            'underweight' => 'Berat badan kurang',
            'stunting_severe' => 'Tinggi badan sangat pendek',
            'stunting' => 'Tinggi badan pendek',
            'wasting_severe' => 'Gizi buruk akut',
            'wasting' => 'Gizi kurang akut',
            'overweight_risk' => 'Risiko gizi lebih',
            'growth_faltering' => 'Pertumbuhan melambat',
            'weight_loss' => 'Berat badan turun',
            'height_shrinkage' => 'Tinggi badan menurun',
            'duplicate_measurement_date' => 'Tanggal ukur ganda',
            'missed_appointment' => 'Jadwal kunjungan terlewat',
            default => ucfirst(str_replace('_', ' ', $flag)),
        };
    }
}
