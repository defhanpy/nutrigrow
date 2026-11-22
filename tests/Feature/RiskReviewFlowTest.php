<?php

namespace Tests\Feature;

use App\Enums\RiskStatus;
use App\Models\Child;
use App\Models\FollowUpRecord;
use App\Models\Guardian;
use App\Models\HealthFacility;
use App\Models\RiskAssessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RiskReviewFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_worker_resolves_a_risk_and_records_follow_up(): void
    {
        [$nakes, $child] = $this->healthWorkerWithChild();
        $risk = RiskAssessment::factory()->high()->create(['child_id' => $child->id]);

        $response = $this->actingAs($nakes)->patch("/tenaga-kesehatan/peninjauan/{$risk->id}/selesai", [
            'note' => 'Sudah ditinjau bersama kader dan keluarga.',
            'action_plan' => 'Pantau berat badan dua minggu lagi.',
        ]);

        $response->assertRedirect('/tenaga-kesehatan/dashboard');
        $this->assertSame(RiskStatus::Resolved, $risk->fresh()->status);
        $this->assertDatabaseHas('follow_up_records', [
            'child_id' => $child->id,
            'category' => 'Arahan tenaga kesehatan',
            'action_plan' => 'Pantau berat badan dua minggu lagi.',
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'risk.resolved', 'entity_id' => $risk->id]);
    }

    public function test_health_worker_can_dismiss_a_risk_with_a_note(): void
    {
        [$nakes, $child] = $this->healthWorkerWithChild();
        $risk = RiskAssessment::factory()->create(['child_id' => $child->id]);

        $this->actingAs($nakes)->patch("/tenaga-kesehatan/peninjauan/{$risk->id}/tutup", [
            'note' => 'Pengukuran sebelumnya keliru, sudah diukur ulang.',
        ])->assertRedirect('/tenaga-kesehatan/dashboard');

        $this->assertSame(RiskStatus::Dismissed, $risk->fresh()->status);
    }

    public function test_health_worker_cannot_review_a_risk_outside_the_puskesmas(): void
    {
        [$nakes] = $this->healthWorkerWithChild();
        $outsider = Child::factory()->create();
        $risk = RiskAssessment::factory()->create(['child_id' => $outsider->id]);

        $this->actingAs($nakes)->patch("/tenaga-kesehatan/peninjauan/{$risk->id}/selesai", [
            'note' => 'Mencoba meninjau wilayah lain.',
        ])->assertForbidden();

        $this->assertSame(RiskStatus::Open, $risk->fresh()->status);
    }

    public function test_review_requires_a_note(): void
    {
        [$nakes, $child] = $this->healthWorkerWithChild();
        $risk = RiskAssessment::factory()->create(['child_id' => $child->id]);

        $this->actingAs($nakes)->patch("/tenaga-kesehatan/peninjauan/{$risk->id}/selesai", [
            'note' => '',
        ])->assertSessionHasErrors('note');

        $this->assertSame(RiskStatus::Open, $risk->fresh()->status);
    }

    public function test_kader_records_and_completes_a_follow_up(): void
    {
        $facility = HealthFacility::factory()->create();
        $kader = User::factory()->kader()->create(['facility_id' => $facility->id]);
        $child = Child::factory()->create(['facility_id' => $facility->id]);

        $this->actingAs($kader)->post("/anak/{$child->id}/tindak-lanjut", [
            'category' => 'Kunjungan rumah',
            'action_plan' => 'Dampingi keluarga menyusun menu mingguan.',
            'target_date' => now()->addWeek()->toDateString(),
        ])->assertRedirect();

        $followUp = FollowUpRecord::query()->where('child_id', $child->id)->sole();
        $this->assertSame($kader->id, $followUp->created_by);

        $this->actingAs($kader)->patch("/tindak-lanjut/{$followUp->id}/selesai", [
            'note' => 'Keluarga sudah membuat menu.',
        ])->assertRedirect();

        $this->assertSame('completed', $followUp->fresh()->status->value);
        $this->assertStringContainsString('Keluarga sudah membuat menu.', $followUp->fresh()->notes);
    }

    public function test_parent_cannot_complete_a_follow_up(): void
    {
        $facility = HealthFacility::factory()->create();
        $parent = User::factory()->orangTua()->create(['facility_id' => $facility->id]);
        $child = Child::factory()->create(['facility_id' => $facility->id]);
        Guardian::factory()->create(['user_id' => $parent->id, 'child_id' => $child->id]);
        $followUp = FollowUpRecord::factory()->create(['child_id' => $child->id]);

        $this->actingAs($parent)->patch("/tindak-lanjut/{$followUp->id}/selesai")->assertForbidden();

        $this->assertSame('pending', $followUp->fresh()->status->value);
    }

    /**
     * @return array{0: User, 1: Child}
     */
    private function healthWorkerWithChild(): array
    {
        $puskesmas = HealthFacility::factory()->puskesmas()->create();
        $posyandu = HealthFacility::factory()->create(['parent_id' => $puskesmas->id]);
        $nakes = User::factory()->tenagaKesehatan()->create(['facility_id' => $puskesmas->id]);
        $child = Child::factory()->create(['facility_id' => $posyandu->id]);

        return [$nakes, $child];
    }
}
