<?php

namespace Tests\Feature;

use App\Enums\MeasurementMethod;
use App\Enums\Sex;
use App\Models\Child;
use App\Services\WhoGrowthEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class WhoGrowthEngineTest extends TestCase
{
    use RefreshDatabase;

    private WhoGrowthEngineService $engine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->engine = app(WhoGrowthEngineService::class);
    }

    public function test_median_weight_for_age_returns_zero_z_score(): void
    {
        $child = $this->child(Sex::LakiLaki, '2025-01-01');

        $result = $this->engine->calculateZScores(
            $child,
            weightKg: 9.65,
            heightCm: 75.7,
            method: MeasurementMethod::Terlentang->value,
            measuredAt: Carbon::parse('2026-01-01'),
        );

        $this->assertSame(365, $result['age_in_days']);
        $this->assertEqualsWithDelta(0.0, $result['wfa_z'], 0.05);
        $this->assertSame('Gizi Baik', $result['wfa_status']);
    }

    public function test_weight_for_age_uses_box_cox_formula_for_negative_two_sd(): void
    {
        $child = $this->child(Sex::LakiLaki, '2025-01-01');

        $result = $this->engine->calculateZScores(
            $child,
            weightKg: 7.74,
            heightCm: 75.7,
            method: MeasurementMethod::Terlentang->value,
            measuredAt: Carbon::parse('2026-01-01'),
        );

        $this->assertEqualsWithDelta(-2.0, $result['wfa_z'], 0.05);
        $this->assertSame('Gizi Kurang', $result['wfa_status']);
    }

    public function test_weight_for_age_below_minus_three_is_classified_as_severe_underweight(): void
    {
        $child = $this->child(Sex::LakiLaki, '2025-01-01');

        $result = $this->engine->calculateZScores(
            $child,
            weightKg: 6.0,
            heightCm: 75.7,
            method: MeasurementMethod::Terlentang->value,
            measuredAt: Carbon::parse('2026-01-01'),
        );

        $this->assertLessThan(-3.0, $result['wfa_z']);
        $this->assertSame('Gizi Buruk', $result['wfa_status']);
    }

    public function test_length_for_age_median_matches_who_reference(): void
    {
        $child = $this->child(Sex::LakiLaki, '2025-01-01');

        $result = $this->engine->calculateZScores(
            $child,
            weightKg: 9.65,
            heightCm: 75.74,
            method: MeasurementMethod::Terlentang->value,
            measuredAt: Carbon::parse('2026-01-01'),
        );

        $this->assertEqualsWithDelta(0.0, $result['lhfa_z'], 0.05);
        $this->assertSame('Normal', $result['lhfa_status']);
    }

    public function test_length_for_age_below_minus_two_is_classified_as_stunted(): void
    {
        $child = $this->child(Sex::LakiLaki, '2025-01-01');

        $result = $this->engine->calculateZScores(
            $child,
            weightKg: 9.65,
            heightCm: 70.5,
            method: MeasurementMethod::Terlentang->value,
            measuredAt: Carbon::parse('2026-01-01'),
        );

        $this->assertLessThan(-2.0, $result['lhfa_z']);
        $this->assertContains($result['lhfa_status'], ['Pendek', 'Sangat Pendek']);
    }

    public function test_weight_for_height_identifies_wasting(): void
    {
        $child = $this->child(Sex::LakiLaki, '2025-01-01');

        $result = $this->engine->calculateZScores(
            $child,
            weightKg: 8.8,
            heightCm: 80.0,
            method: MeasurementMethod::Berdiri->value,
            measuredAt: Carbon::parse('2026-01-01'),
        );

        $this->assertLessThan(-2.0, $result['wfh_z']);
        $this->assertGreaterThan(-3.0, $result['wfh_z']);
        $this->assertSame('Gizi Kurang (Wasting)', $result['wfh_status']);
    }

    public function test_standing_height_under_two_years_is_corrected_upward(): void
    {
        $corrected = $this->engine->correctHeight(80.0, 500, MeasurementMethod::Berdiri->value);
        $recumbent = $this->engine->correctHeight(80.0, 500, MeasurementMethod::Terlentang->value);

        $this->assertSame(80.7, $corrected);
        $this->assertSame(80.0, $recumbent);
    }

    public function test_recumbent_height_from_two_years_is_corrected_downward(): void
    {
        $corrected = $this->engine->correctHeight(80.0, 800, MeasurementMethod::Terlentang->value);
        $standing = $this->engine->correctHeight(80.0, 800, MeasurementMethod::Berdiri->value);

        $this->assertSame(79.3, $corrected);
        $this->assertSame(80.0, $standing);
    }

    public function test_height_correction_is_applied_before_z_score_calculation(): void
    {
        $child = $this->child(Sex::LakiLaki, '2025-01-01');

        $standing = $this->engine->calculateZScores(
            $child,
            weightKg: 9.65,
            heightCm: 75.0,
            method: MeasurementMethod::Berdiri->value,
            measuredAt: Carbon::parse('2026-01-01'),
        );

        $this->assertSame(75.7, $standing['corrected_height_cm']);
    }

    public function test_z_score_is_linearly_extrapolated_beyond_positive_three_sd(): void
    {
        $z = $this->zScoreFromPublicApi(weightKg: 15.0);

        $this->assertGreaterThan(3.0, $z);
        $this->assertLessThan(6.0, $z);
    }

    public function test_measurement_older_than_five_years_is_not_scored(): void
    {
        $child = $this->child(Sex::LakiLaki, '2019-01-01');

        $result = $this->engine->calculateZScores(
            $child,
            weightKg: 18.0,
            heightCm: 110.0,
            method: MeasurementMethod::Berdiri->value,
            measuredAt: Carbon::parse('2026-01-01'),
        );

        $this->assertNull($result['wfa_z']);
        $this->assertNull($result['lhfa_z']);
        $this->assertNull($result['wfa_status']);
    }

    public function test_trajectory_reports_faltering_when_weight_gain_stalls(): void
    {
        $child = $this->child(Sex::LakiLaki, '2025-01-01');
        $this->measurement($child, '2026-06-01', 8.6, 73.0);
        $this->measurement($child, '2026-07-01', 8.62, 73.5);
        $this->measurement($child, '2026-08-01', 8.64, 74.0);

        $trajectory = $this->engine->evaluateTrajectory($child);
        $codes = array_column($trajectory['findings'], 'code');

        $this->assertContains('growth_faltering', $codes);
    }

    public function test_trajectory_reports_weight_loss(): void
    {
        $child = $this->child(Sex::LakiLaki, '2025-01-01');
        $this->measurement($child, '2026-06-01', 9.5, 74.0);
        $this->measurement($child, '2026-07-01', 9.0, 74.5);

        $codes = array_column($this->engine->evaluateTrajectory($child)['findings'], 'code');

        $this->assertContains('weight_loss', $codes);
    }

    public function test_trajectory_reports_height_shrinkage(): void
    {
        $child = $this->child(Sex::LakiLaki, '2025-01-01');
        $this->measurement($child, '2026-06-01', 9.5, 75.0);
        $this->measurement($child, '2026-07-01', 9.8, 73.0);

        $codes = array_column($this->engine->evaluateTrajectory($child)['findings'], 'code');

        $this->assertContains('height_shrinkage', $codes);
    }

    public function test_trajectory_reports_duplicate_measurement_date(): void
    {
        $child = $this->child(Sex::LakiLaki, '2025-01-01');
        $this->measurement($child, '2026-06-01', 9.5, 75.0);
        $this->measurement($child, '2026-06-01', 9.6, 75.2);

        $codes = array_column($this->engine->evaluateTrajectory($child)['findings'], 'code');

        $this->assertContains('duplicate_measurement_date', $codes);
    }

    public function test_trajectory_reports_long_gap_between_visits(): void
    {
        $child = $this->child(Sex::LakiLaki, '2025-01-01');
        $this->measurement($child, '2026-01-01', 9.0, 74.0);
        $this->measurement($child, '2026-05-01', 10.0, 78.0);

        $codes = array_column($this->engine->evaluateTrajectory($child)['findings'], 'code');

        $this->assertContains('missed_appointment', $codes);
    }

    public function test_trajectory_returns_plain_language_without_diagnosis_claim(): void
    {
        $child = $this->child(Sex::LakiLaki, '2025-01-01');
        $this->measurement($child, '2026-06-01', 9.5, 74.0);
        $this->measurement($child, '2026-07-01', 9.0, 74.5);

        $trajectory = $this->engine->evaluateTrajectory($child);

        $this->assertStringContainsString('bukan diagnosis', $trajectory['summary']);
        $this->assertStringNotContainsString('LMS', $trajectory['summary']);
    }

    public function test_trajectory_collapses_repeated_findings_into_one_entry(): void
    {
        $child = $this->child(Sex::LakiLaki, '2025-01-01');
        $this->measurement($child, '2026-04-01', 8.0, 71.0);
        $this->measurement($child, '2026-05-01', 8.01, 71.5);
        $this->measurement($child, '2026-06-01', 8.02, 72.0);

        $trajectory = $this->engine->evaluateTrajectory($child);
        $faltering = array_values(array_filter(
            $trajectory['findings'],
            fn (array $finding): bool => $finding['code'] === 'growth_faltering',
        ));

        $this->assertCount(1, $faltering);
        $this->assertSame(2, $faltering[0]['occurrences']);
    }

    public function test_trajectory_requires_at_least_two_visits(): void
    {
        $child = $this->child(Sex::LakiLaki, '2025-01-01');
        $this->measurement($child, '2026-06-01', 9.5, 75.0);

        $trajectory = $this->engine->evaluateTrajectory($child);

        $this->assertSame([], $trajectory['findings']);
        $this->assertSame(1, $trajectory['visits_analyzed']);
    }

    private function child(Sex $sex, string $dateOfBirth): Child
    {
        return Child::factory()->create([
            'sex' => $sex,
            'date_of_birth' => $dateOfBirth,
        ]);
    }

    private function measurement(Child $child, string $date, float $weight, float $height): void
    {
        $child->measurements()->create([
            'measured_at' => $date,
            'weight_kg' => $weight,
            'height_cm' => $height,
            'method' => MeasurementMethod::Terlentang->value,
        ]);
    }

    private function zScoreFromPublicApi(float $weightKg): float
    {
        $child = $this->child(Sex::LakiLaki, '2025-01-01');

        return $this->engine->calculateZScores(
            $child,
            weightKg: $weightKg,
            heightCm: 75.7,
            method: MeasurementMethod::Terlentang->value,
            measuredAt: Carbon::parse('2026-01-01'),
        )['wfa_z'];
    }
}
