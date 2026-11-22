<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\FollowUpRecord;
use App\Models\Guardian;
use App\Models\HealthFacility;
use App\Models\RiskAssessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_kader_dashboard_lists_only_facility_children(): void
    {
        $facility = HealthFacility::factory()->create();
        $otherFacility = HealthFacility::factory()->create();
        $kader = User::factory()->kader()->create(['facility_id' => $facility->id]);
        $ownChild = Child::factory()->create(['facility_id' => $facility->id, 'full_name' => 'Afiqah Zahra']);
        Child::factory()->create(['facility_id' => $otherFacility->id, 'full_name' => 'Anak Fasilitas Lain']);

        $response = $this->actingAs($kader)->get('/kader/dashboard');

        $response->assertOk();
        $response->assertSee($ownChild->full_name);
        $response->assertDontSee('Anak Fasilitas Lain');
        $response->assertSee('Pengukuran baru');
    }

    public function test_kader_dashboard_shows_growth_chart_and_follow_up_log(): void
    {
        $facility = HealthFacility::factory()->create();
        $kader = User::factory()->kader()->create(['facility_id' => $facility->id]);
        $child = Child::factory()->male()->agedMonths(18)->create(['facility_id' => $facility->id]);
        FollowUpRecord::factory()->create([
            'child_id' => $child->id,
            'created_by' => $kader->id,
            'action_plan' => 'Dampingi ibu menyusun menu protein hewani.',
        ]);

        $response = $this->actingAs($kader)->get('/kader/dashboard');

        $response->assertOk();
        $response->assertSee('Grafik pertumbuhan');
        $response->assertSee('Dampingi ibu menyusun menu protein hewani.');
    }

    public function test_parent_portal_shows_only_their_own_child(): void
    {
        $facility = HealthFacility::factory()->create();
        $parent = User::factory()->orangTua()->create(['facility_id' => $facility->id]);
        $ownChild = Child::factory()->female()->agedMonths(20)->create([
            'facility_id' => $facility->id,
            'full_name' => 'Afiqah Zahra',
        ]);
        $otherChild = Child::factory()->create([
            'facility_id' => $facility->id,
            'full_name' => 'Anak Keluarga Lain',
        ]);
        Guardian::factory()->create([
            'user_id' => $parent->id,
            'child_id' => $ownChild->id,
        ]);

        $response = $this->actingAs($parent)->get('/orang-tua/beranda');

        $response->assertOk();
        $response->assertSee($ownChild->full_name);
        $response->assertDontSee($otherChild->full_name);
    }

    public function test_parent_portal_shows_empathetic_schedule_and_nutrition(): void
    {
        $facility = HealthFacility::factory()->create();
        $parent = User::factory()->orangTua()->create(['facility_id' => $facility->id]);
        $child = Child::factory()->female()->agedMonths(14)->create(['facility_id' => $facility->id]);
        Guardian::factory()->create(['user_id' => $parent->id, 'child_id' => $child->id]);

        $response = $this->actingAs($parent)->get('/orang-tua/beranda');

        $response->assertOk();
        $response->assertSee('Jadwal Posyandu');
        $response->assertSee('Menu seimbang dari bahan pangan lokal');
        $response->assertSee('Daun kelor');
    }

    public function test_parent_cannot_open_another_child_profile(): void
    {
        $parent = User::factory()->orangTua()->create();
        $stranger = Child::factory()->create();

        $this->actingAs($parent)->get("/anak/{$stranger->id}")->assertForbidden();
    }

    public function test_health_worker_sees_open_risk_queue_across_posyandu(): void
    {
        $puskesmas = HealthFacility::factory()->puskesmas()->create();
        $posyandu = HealthFacility::factory()->create(['parent_id' => $puskesmas->id]);
        $nakes = User::factory()->tenagaKesehatan()->create(['facility_id' => $puskesmas->id]);
        $child = Child::factory()->create([
            'facility_id' => $posyandu->id,
            'full_name' => 'Bilal Akbar',
        ]);
        RiskAssessment::factory()->high()->create(['child_id' => $child->id]);

        $response = $this->actingAs($nakes)->get('/tenaga-kesehatan/dashboard');

        $response->assertOk();
        $response->assertSee('Antrean peninjauan');
        $response->assertSee('Bilal Akbar');
    }

    public function test_health_worker_cannot_see_children_outside_the_puskesmas(): void
    {
        $puskesmas = HealthFacility::factory()->puskesmas()->create();
        $otherPuskesmas = HealthFacility::factory()->puskesmas()->create();
        $nakes = User::factory()->tenagaKesehatan()->create(['facility_id' => $puskesmas->id]);
        $child = Child::factory()->create(['facility_id' => $otherPuskesmas->id]);
        RiskAssessment::factory()->create(['child_id' => $child->id, 'reason' => 'Tanda dari wilayah lain.']);

        $response = $this->actingAs($nakes)->get('/tenaga-kesehatan/dashboard');

        $response->assertOk();
        $response->assertDontSee('Tanda dari wilayah lain.');
    }

    public function test_puskesmas_dashboard_aggregates_territory_kpis(): void
    {
        $puskesmas = HealthFacility::factory()->puskesmas()->create();
        $posyandu = HealthFacility::factory()->create(['parent_id' => $puskesmas->id]);
        $admin = User::factory()->adminPuskesmas()->create(['facility_id' => $puskesmas->id]);
        Child::factory()->count(3)->create(['facility_id' => $posyandu->id]);

        $response = $this->actingAs($admin)->get('/puskesmas/dashboard');

        $response->assertOk();
        $response->assertSee('Total sasaran');
        $response->assertSee('Cakupan D/S');
        $response->assertSee('Prevalensi stunting');
        $response->assertSee('Prevalensi wasting');
        $response->assertSee($posyandu->name);
    }

    public function test_puskesmas_report_downloads_a_csv(): void
    {
        $puskesmas = HealthFacility::factory()->puskesmas()->create();
        $admin = User::factory()->adminPuskesmas()->create(['facility_id' => $puskesmas->id]);

        $response = $this->actingAs($admin)->get('/puskesmas/laporan/unduh');

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_user_screens_do_not_expose_technical_jargon(): void
    {
        $facility = HealthFacility::factory()->create();
        $kader = User::factory()->kader()->create(['facility_id' => $facility->id]);
        $child = Child::factory()->male()->agedMonths(18)->create(['facility_id' => $facility->id]);

        $response = $this->actingAs($kader)->get('/kader/dashboard');

        $response->assertOk();
        $response->assertDontSee('PostgreSQL');
        $response->assertDontSee('JWT');
        $response->assertDontSee('Box-Cox');
        $response->assertDontSee('LMS');
        $response->assertDontSee('z =');
        $response->assertDontSee('Endpoint');
    }

    public function test_login_page_does_not_expose_technical_jargon(): void
    {
        $response = $this->get('/masuk');

        $response->assertOk();
        $response->assertDontSee('PostgreSQL');
        $response->assertDontSee('JWT');
        $response->assertDontSee('LMS');
    }
}
