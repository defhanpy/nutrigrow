<?php

namespace Tests\Feature;

use App\Enums\MeasurementMethod;
use App\Models\Child;
use App\Models\HealthFacility;
use App\Models\Measurement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeasurementFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_kader_records_a_measurement_and_assessment(): void
    {
        $facility = HealthFacility::factory()->create();
        $kader = User::factory()->kader()->create(['facility_id' => $facility->id]);
        $child = Child::factory()->male()->create([
            'facility_id' => $facility->id,
            'date_of_birth' => now()->subYear()->toDateString(),
        ]);

        $response = $this->actingAs($kader)->post("/anak/{$child->id}/pengukuran", [
            'measured_at' => now()->toDateString(),
            'weight_kg' => 9.65,
            'height_cm' => 75.7,
            'lila_cm' => 14.5,
            'method' => MeasurementMethod::Terlentang->value,
            'notes' => 'Kunjungan rutin bulan ini.',
        ]);

        $response->assertRedirect("/kader/dashboard?child={$child->id}");
        $response->assertSessionHas('status');

        $measurement = Measurement::query()->where('child_id', $child->id)->sole();
        $this->assertSame($kader->id, $measurement->measured_by);
        $this->assertSame('9.65', $measurement->weight_kg);
        $this->assertNotNull($measurement->assessment);
        $this->assertSame('Gizi Baik', $measurement->assessment->wfa_status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'measurement.recorded',
            'entity_id' => $measurement->id,
        ]);
    }

    public function test_measurement_validation_rejects_impossible_values(): void
    {
        $facility = HealthFacility::factory()->create();
        $kader = User::factory()->kader()->create(['facility_id' => $facility->id]);
        $child = Child::factory()->create(['facility_id' => $facility->id]);

        $response = $this->actingAs($kader)->post("/anak/{$child->id}/pengukuran", [
            'measured_at' => now()->addDay()->toDateString(),
            'weight_kg' => 0,
            'height_cm' => 5,
            'method' => 'melayang',
        ]);

        $response->assertSessionHasErrors(['measured_at', 'weight_kg', 'height_cm', 'method']);
        $this->assertDatabaseCount('measurements', 0);
    }

    public function test_kader_cannot_record_a_measurement_for_a_child_outside_the_facility(): void
    {
        $kader = User::factory()->kader()->create();
        $child = Child::factory()->create();

        $this->actingAs($kader)->post("/anak/{$child->id}/pengukuran", [
            'measured_at' => now()->toDateString(),
            'weight_kg' => 9.65,
            'height_cm' => 75.7,
            'method' => MeasurementMethod::Terlentang->value,
        ])->assertForbidden();

        $this->assertDatabaseCount('measurements', 0);
    }

    public function test_parent_cannot_record_a_measurement(): void
    {
        $parent = User::factory()->orangTua()->create();
        $child = Child::factory()->create();

        $this->actingAs($parent)->post("/anak/{$child->id}/pengukuran", [
            'measured_at' => now()->toDateString(),
            'weight_kg' => 9.65,
            'height_cm' => 75.7,
            'method' => MeasurementMethod::Terlentang->value,
        ])->assertForbidden();
    }

    public function test_preview_returns_plain_indicator_labels(): void
    {
        $facility = HealthFacility::factory()->create();
        $kader = User::factory()->kader()->create(['facility_id' => $facility->id]);
        $child = Child::factory()->male()->create([
            'facility_id' => $facility->id,
            'date_of_birth' => now()->subYear()->toDateString(),
        ]);

        $response = $this->actingAs($kader)->postJson("/anak/{$child->id}/pengukuran/pratinjau", [
            'measured_at' => now()->toDateString(),
            'weight_kg' => 9.65,
            'height_cm' => 75.7,
            'method' => MeasurementMethod::Terlentang->value,
        ]);

        $response->assertOk();
        $response->assertJsonPath('overall_status', 'Gizi Baik');
        $response->assertJsonCount(3, 'indicators');
        $response->assertJsonFragment(['label' => 'Berat menurut usia']);
        $response->assertJsonMissingPath('indicators.0.z_score');
    }

    public function test_new_measurement_creates_a_risk_flag_for_low_weight(): void
    {
        $facility = HealthFacility::factory()->create();
        $kader = User::factory()->kader()->create(['facility_id' => $facility->id]);
        $child = Child::factory()->male()->create([
            'facility_id' => $facility->id,
            'date_of_birth' => now()->subYear()->toDateString(),
        ]);

        $this->actingAs($kader)->post("/anak/{$child->id}/pengukuran", [
            'measured_at' => now()->toDateString(),
            'weight_kg' => 6.0,
            'height_cm' => 75.7,
            'method' => MeasurementMethod::Terlentang->value,
        ]);

        $this->assertDatabaseHas('risk_assessments', [
            'child_id' => $child->id,
            'flag' => 'underweight_severe',
            'status' => 'open',
        ]);
    }
}
