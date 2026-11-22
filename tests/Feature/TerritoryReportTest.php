<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\GrowthAssessment;
use App\Models\HealthFacility;
use App\Models\Measurement;
use App\Models\User;
use App\Services\TerritoryReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TerritoryReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_counts_active_children_in_the_puskesmas_territory_only(): void
    {
        [$puskesmas, $posyandu] = $this->territory();
        $otherPuskesmas = HealthFacility::factory()->puskesmas()->create();
        Child::factory()->count(2)->create(['facility_id' => $posyandu->id]);
        Child::factory()->count(3)->create(['facility_id' => $otherPuskesmas->id]);

        $summary = app(TerritoryReportService::class)->summary($this->admin($puskesmas));

        $this->assertSame(2, $summary['total_target']);
    }

    public function test_summary_reports_weighing_coverage_for_the_current_month(): void
    {
        [$puskesmas, $posyandu] = $this->territory();
        $measured = Child::factory()->create(['facility_id' => $posyandu->id]);
        $unmeasured = Child::factory()->create(['facility_id' => $posyandu->id]);

        $this->measurement($measured, now()->startOfMonth()->addDays(2)->toDateString(), 9.0, 75.0);
        $this->measurement($unmeasured, now()->subMonths(2)->toDateString(), 8.0, 71.0);

        $summary = app(TerritoryReportService::class)->summary($this->admin($puskesmas));

        $this->assertSame(2, $summary['total_target']);
        $this->assertSame(1, $summary['measured_this_month']);
        $this->assertSame(50.0, $summary['coverage_percent']);
    }

    public function test_prevalence_uses_the_most_recent_measurement_by_date_not_insertion_order(): void
    {
        [$puskesmas, $posyandu] = $this->territory();
        $child = Child::factory()->create(['facility_id' => $posyandu->id]);

        // Latest visit is inserted first so its id is lower than the backdated visit.
        $latest = $this->measurement($child, now()->toDateString(), 9.0, 75.0);
        $this->assessment($latest, 'Normal');
        $older = $this->measurement($child, now()->subMonth()->toDateString(), 8.0, 66.0);
        $this->assessment($older, 'Sangat Pendek');

        $summary = app(TerritoryReportService::class)->summary($this->admin($puskesmas));

        $this->assertSame(0, $summary['stunting_count']);
    }

    public function test_posyandu_comparison_returns_one_row_per_posyandu(): void
    {
        [$puskesmas, $posyandu] = $this->territory();
        $second = HealthFacility::factory()->create(['parent_id' => $puskesmas->id]);
        Child::factory()->create(['facility_id' => $posyandu->id]);
        Child::factory()->count(2)->create(['facility_id' => $second->id]);

        $rows = app(TerritoryReportService::class)->posyanduComparison($this->admin($puskesmas));

        $this->assertCount(2, $rows);
        $this->assertSame(2, $rows->firstWhere('code', $second->code)['total_target']);
    }

    public function test_report_download_includes_a_header_and_one_row_per_posyandu(): void
    {
        [$puskesmas, $posyandu] = $this->territory();
        Child::factory()->create(['facility_id' => $posyandu->id]);

        $response = $this->actingAs($this->admin($puskesmas))->get('/puskesmas/laporan/unduh');

        $response->assertOk();
        $body = $response->streamedContent();
        $this->assertStringContainsString('Posyandu', $body);
        $this->assertStringContainsString($posyandu->name, $body);
    }

    /**
     * @return array{0: HealthFacility, 1: HealthFacility}
     */
    private function territory(): array
    {
        $puskesmas = HealthFacility::factory()->puskesmas()->create();
        $posyandu = HealthFacility::factory()->create(['parent_id' => $puskesmas->id]);

        return [$puskesmas, $posyandu];
    }

    private function admin(HealthFacility $puskesmas): User
    {
        return User::factory()->adminPuskesmas()->create(['facility_id' => $puskesmas->id]);
    }

    private function measurement(Child $child, string $date, float $weight, float $height): Measurement
    {
        return $child->measurements()->create([
            'measured_at' => $date,
            'weight_kg' => $weight,
            'height_cm' => $height,
            'method' => 'terlentang',
        ]);
    }

    private function assessment(Measurement $measurement, string $lengthStatus): GrowthAssessment
    {
        return $measurement->assessment()->create([
            'child_id' => $measurement->child_id,
            'age_in_days' => 400,
            'wfa_status' => 'Gizi Baik',
            'lhfa_status' => $lengthStatus,
            'wfh_status' => 'Normal',
            'bfa_status' => 'Normal',
            'overall_status' => $lengthStatus,
        ]);
    }
}
