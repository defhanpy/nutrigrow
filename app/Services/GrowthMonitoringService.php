<?php

namespace App\Services;

use App\Enums\FollowUpStatus;
use App\Enums\MeasurementMethod;
use App\Enums\RiskSeverity;
use App\Enums\RiskStatus;
use App\Models\AuditLog;
use App\Models\Child;
use App\Models\GrowthAssessment;
use App\Models\Measurement;
use App\Models\RiskAssessment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class GrowthMonitoringService
{
    public function __construct(
        private readonly WhoGrowthEngineService $growthEngine,
        private readonly NutritionService $nutrition,
    ) {}

    /**
     * Store a measurement, its WHO assessment, and the status-based care flags.
     *
     * @param  array{measured_at: string|Carbon, weight_kg: float, height_cm: float, lila_cm?: float|null, method: string, notes?: string|null}  $attributes
     */
    public function record(Child $child, array $attributes, ?User $actor = null): Measurement
    {
        return DB::transaction(function () use ($child, $attributes, $actor): Measurement {
            $measuredAt = Carbon::parse($attributes['measured_at']);
            $method = $attributes['method'] instanceof MeasurementMethod
                ? $attributes['method']->value
                : (string) $attributes['method'];

            $measurement = $child->measurements()->create([
                'measured_by' => $actor?->id,
                'measured_at' => $measuredAt->toDateString(),
                'weight_kg' => $attributes['weight_kg'],
                'height_cm' => $attributes['height_cm'],
                'lila_cm' => $attributes['lila_cm'] ?? null,
                'method' => $method,
                'notes' => $attributes['notes'] ?? null,
            ]);

            $result = $this->growthEngine->calculateZScores(
                $child,
                (float) $attributes['weight_kg'],
                (float) $attributes['height_cm'],
                $method,
                $measuredAt,
            );

            $measurement->assessment()->create([
                'child_id' => $child->id,
                'age_in_days' => $result['age_in_days'],
                'wfa_z' => $result['wfa_z'],
                'lhfa_z' => $result['lhfa_z'],
                'wfh_z' => $result['wfh_z'],
                'bfa_z' => $result['bfa_z'],
                'wfa_status' => $result['wfa_status'],
                'lhfa_status' => $result['lhfa_status'],
                'wfh_status' => $result['wfh_status'],
                'bfa_status' => $result['bfa_status'],
                'overall_status' => $result['overall_status'],
            ]);

            $measurement->load('assessment');
            $this->flagStatusRisks($child, $measurement);

            AuditLog::query()->create([
                'user_id' => $actor?->id,
                'action' => 'measurement.recorded',
                'entity' => 'measurement',
                'entity_id' => $measurement->id,
                'detail' => [
                    'child_id' => $child->id,
                    'age_in_days' => $result['age_in_days'],
                    'overall_status' => $result['overall_status'],
                ],
                'ip_address' => request()->ip(),
            ]);

            return $measurement;
        });
    }

    /**
     * Rebuild the explainable trajectory flags for a child.
     *
     * @return array<int, array{code: string, title: string, description: string, severity: string, evidence: array<string, mixed>}>
     */
    public function refreshTrajectoryRisks(Child $child): array
    {
        $trajectory = $this->growthEngine->evaluateTrajectory($child);
        $latestMeasurement = $child->measurements()->latest('measured_at')->first();

        foreach ($trajectory['findings'] as $finding) {
            if (! in_array($finding['severity'], ['medium', 'high'], true)) {
                continue;
            }

            RiskAssessment::query()->firstOrCreate(
                [
                    'child_id' => $child->id,
                    'flag' => $finding['code'],
                    'status' => RiskStatus::Open,
                ],
                [
                    'measurement_id' => $latestMeasurement?->id,
                    'severity' => $finding['severity'],
                    'reason' => $finding['description'],
                    'evidence' => [
                        ...$finding['evidence'],
                        'title' => $finding['title'],
                    ],
                ],
            );
        }

        return $trajectory['findings'];
    }

    public function resolveRisk(RiskAssessment $risk, ?User $actor, string $note): void
    {
        DB::transaction(function () use ($risk, $actor, $note): void {
            $risk->update(['status' => RiskStatus::Resolved]);

            AuditLog::query()->create([
                'user_id' => $actor?->id,
                'action' => 'risk.resolved',
                'entity' => 'risk_assessment',
                'entity_id' => $risk->id,
                'detail' => ['note' => $note],
                'ip_address' => request()->ip(),
            ]);
        });
    }

    public function createFollowUp(Child $child, User $actor, string $category, string $plan, ?string $targetDate, ?string $notes = null): void
    {
        $child->followUps()->create([
            'created_by' => $actor->id,
            'category' => $category,
            'action_plan' => $plan,
            'target_date' => $targetDate,
            'status' => FollowUpStatus::Pending,
            'notes' => $notes,
        ]);

        AuditLog::query()->create([
            'user_id' => $actor->id,
            'action' => 'follow_up.created',
            'entity' => 'follow_up_record',
            'entity_id' => $child->id,
            'detail' => ['category' => $category],
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * @return array{headline: string, focus: string}
     */
    public function careSummary(Child $child): array
    {
        $assessment = $child->assessments()->latest('id')->first();
        $recommendation = $this->nutrition->recommendForChild($child, $assessment);

        return [
            'headline' => $recommendation['headline'],
            'focus' => $recommendation['focus'],
        ];
    }

    private function flagStatusRisks(Child $child, Measurement $measurement): void
    {
        $assessment = $measurement->assessment;

        if (! $assessment instanceof GrowthAssessment) {
            return;
        }

        $flags = [];

        $this->addFlag($flags, $assessment->wfa_status, [
            'Gizi Buruk' => ['underweight_severe', 'high', 'Berat badan anak jauh di bawah rentang usianya dan perlu segera ditinjau tenaga kesehatan.'],
            'Gizi Kurang' => ['underweight', 'medium', 'Berat badan anak berada di bawah rentang usianya. Perlu pendampingan makan dan pemantauan ulang.'],
        ]);

        $this->addFlag($flags, $assessment->lhfa_status, [
            'Sangat Pendek' => ['stunting_severe', 'high', 'Tinggi badan anak jauh di bawah rentang usianya. Perlu peninjauan tumbuh kembang dan pendampingan gizi.'],
            'Pendek' => ['stunting', 'medium', 'Tinggi badan anak berada di bawah rentang usianya. Perlu pemantauan pertumbuhan berkelanjutan.'],
        ]);

        $this->addFlag($flags, $assessment->wfh_status, [
            'Gizi Buruk (Wasting Berat)' => ['wasting_severe', 'high', 'Berat badan anak tidak seimbang dengan tinggi badannya dan perlu rujukan segera.'],
            'Gizi Kurang (Wasting)' => ['wasting', 'high', 'Berat badan anak tidak seimbang dengan tinggi badannya dan perlu peninjauan tenaga kesehatan.'],
        ]);

        if (in_array($assessment->bfa_status, ['Gizi Lebih', 'Berisiko Gizi Lebih'], true)) {
            $flags[] = [
                'flag' => 'overweight_risk',
                'severity' => 'low',
                'reason' => 'Berat badan anak berada di atas rentang tinggi badannya. Perlu pemantauan pola makan dan aktivitas.',
            ];
        }

        foreach ($flags as $flag) {
            $existing = RiskAssessment::query()
                ->where('child_id', $child->id)
                ->where('flag', $flag['flag'])
                ->where('status', RiskStatus::Open)
                ->first();

            if ($existing !== null) {
                $existing->update([
                    'measurement_id' => $measurement->id,
                    'severity' => RiskSeverity::from($flag['severity']),
                    'reason' => $flag['reason'],
                    'evidence' => [
                        'wfa_z' => $assessment->wfa_z,
                        'lhfa_z' => $assessment->lhfa_z,
                        'wfh_z' => $assessment->wfh_z,
                        'bfa_z' => $assessment->bfa_z,
                        'overall_status' => $assessment->overall_status,
                    ],
                ]);

                continue;
            }

            RiskAssessment::query()->create([
                'child_id' => $child->id,
                'measurement_id' => $measurement->id,
                'flag' => $flag['flag'],
                'severity' => RiskSeverity::from($flag['severity']),
                'reason' => $flag['reason'],
                'evidence' => [
                    'wfa_z' => $assessment->wfa_z,
                    'lhfa_z' => $assessment->lhfa_z,
                    'wfh_z' => $assessment->wfh_z,
                    'bfa_z' => $assessment->bfa_z,
                    'overall_status' => $assessment->overall_status,
                ],
                'status' => RiskStatus::Open,
            ]);
        }
    }

    /**
     * @param  array<int, array{flag: string, severity: string, reason: string}>  $flags
     * @param  array<string, array{0: string, 1: string, 2: string}>  $mapping
     */
    private function addFlag(array &$flags, ?string $status, array $mapping): void
    {
        if ($status === null || ! isset($mapping[$status])) {
            return;
        }

        [$flag, $severity, $reason] = $mapping[$status];
        $flags[] = ['flag' => $flag, 'severity' => $severity, 'reason' => $reason];
    }
}
