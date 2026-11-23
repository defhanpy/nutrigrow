<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Child;
use App\Models\Guardian;
use App\Models\HealthFacility;
use App\Models\RiskAssessment;
use App\Models\User;
use App\Support\PortalNavigation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_every_dedicated_page(): void
    {
        $routes = [
            '/kader/pengukuran',
            '/kader/analisis',
            '/orang-tua/pertumbuhan',
            '/orang-tua/jadwal',
            '/tenaga-kesehatan/peninjauan',
            '/tenaga-kesehatan/analisis',
            '/puskesmas/posyandu',
            '/puskesmas/pengguna',
            '/puskesmas/pengaturan',
        ];

        foreach ($routes as $route) {
            $this->get($route)->assertRedirect('/masuk');
        }
    }

    public function test_every_role_sidebar_item_points_to_a_distinct_named_route(): void
    {
        $expected = [
            Role::Kader->value => [
                'kader.dashboard', 'children.index', 'kader.measurements',
                'kader.analytics', 'follow-ups.index', 'nutrition.index',
                'nutrition.chat',
            ],
            Role::OrangTua->value => [
                'ortu.dashboard', 'ortu.growth', 'nutrition.chat',
                'nutrition.index', 'ortu.schedule', 'profile.show',
            ],
            Role::TenagaKesehatan->value => [
                'nakes.dashboard', 'nakes.reviews', 'children.index',
                'nakes.analytics', 'follow-ups.index',
            ],
            Role::AdminPuskesmas->value => [
                'puskesmas.dashboard', 'puskesmas.posyandu', 'puskesmas.users',
                'puskesmas.reports', 'puskesmas.settings',
            ],
        ];

        foreach ($expected as $role => $routes) {
            $user = User::factory()->role(Role::from($role))->create();
            $navigation = PortalNavigation::for($user);

            $this->assertCount(count($routes), $navigation);
            $this->assertSame(
                array_map(fn (string $name): string => route($name), $routes),
                array_column($navigation, 'url'),
            );
            $this->assertStringNotContainsString('#', implode('', array_column($navigation, 'url')));
            $this->assertSame(count($routes), count(array_unique(array_column($navigation, 'url'))));
        }
    }

    public function test_kader_can_open_measurement_history_and_analytics(): void
    {
        $facility = HealthFacility::factory()->create();
        $kader = User::factory()->kader()->create(['facility_id' => $facility->id]);
        $child = Child::factory()->male()->agedMonths(18)->create([
            'facility_id' => $facility->id,
            'full_name' => 'Afiqah Zahra',
        ]);
        $child->measurements()->create([
            'measured_at' => now()->toDateString(),
            'weight_kg' => 9.6,
            'height_cm' => 75.7,
            'method' => 'terlentang',
        ]);

        $this->actingAs($kader)->get('/kader/pengukuran')
            ->assertOk()
            ->assertSee('Riwayat pengukuran')
            ->assertSee('Afiqah Zahra')
            ->assertSee('Catatan penimbangan');

        $this->actingAs($kader)->get('/kader/analisis')
            ->assertOk()
            ->assertSee('Analisis pertumbuhan')
            ->assertSee('Berat menurut usia')
            ->assertSee('Tinggi menurut usia')
            ->assertSee('Berat menurut tinggi')
            ->assertSee('Balita perlu pantauan');
    }

    public function test_kader_measurement_history_filters_by_child(): void
    {
        $facility = HealthFacility::factory()->create();
        $kader = User::factory()->kader()->create(['facility_id' => $facility->id]);
        $own = Child::factory()->create(['facility_id' => $facility->id, 'full_name' => 'Anak Dipilih']);
        $other = Child::factory()->create(['facility_id' => $facility->id, 'full_name' => 'Anak Lain']);

        $own->measurements()->create([
            'measured_at' => now()->toDateString(),
            'weight_kg' => 9.6,
            'height_cm' => 75.7,
            'method' => 'terlentang',
        ]);
        $other->measurements()->create([
            'measured_at' => now()->toDateString(),
            'weight_kg' => 12.34,
            'height_cm' => 88.8,
            'method' => 'terlentang',
        ]);

        $this->actingAs($kader)->get('/kader/pengukuran?child='.$own->id)
            ->assertOk()
            ->assertSee('Riwayat Anak Dipilih')
            ->assertSee('1 catatan')
            ->assertDontSee('12.34 kg');
    }

    public function test_parent_can_open_growth_and_schedule_pages_for_own_child(): void
    {
        $facility = HealthFacility::factory()->create();
        $parent = User::factory()->orangTua()->create(['facility_id' => $facility->id]);
        $child = Child::factory()->female()->agedMonths(14)->create([
            'facility_id' => $facility->id,
            'full_name' => 'Afiqah Zahra',
        ]);
        Guardian::factory()->create(['user_id' => $parent->id, 'child_id' => $child->id]);

        $this->actingAs($parent)->get('/orang-tua/pertumbuhan')
            ->assertOk()
            ->assertSee('Afiqah Zahra')
            ->assertSee('Kurva pertumbuhan (KMS)')
            ->assertSee('Riwayat penimbangan')
            ->assertSee('Tonggak usia');

        $this->actingAs($parent)->get('/orang-tua/jadwal')
            ->assertOk()
            ->assertSee('Jadwal Posyandu')
            ->assertSee('Kalender')
            ->assertSee('Imunisasi wajib')
            ->assertSee('Vitamin A dan obat cacing');
    }

    public function test_parent_growth_page_never_shows_another_family_child(): void
    {
        $facility = HealthFacility::factory()->create();
        $parent = User::factory()->orangTua()->create(['facility_id' => $facility->id]);
        $ownChild = Child::factory()->female()->agedMonths(20)->create([
            'facility_id' => $facility->id,
            'full_name' => 'Anak Sendiri',
        ]);
        Child::factory()->create(['facility_id' => $facility->id, 'full_name' => 'Anak Keluarga Lain']);
        Guardian::factory()->create(['user_id' => $parent->id, 'child_id' => $ownChild->id]);

        $this->actingAs($parent)->get('/orang-tua/pertumbuhan')
            ->assertOk()
            ->assertSee('Anak Sendiri')
            ->assertDontSee('Anak Keluarga Lain');
    }

    public function test_health_worker_can_open_review_queue_with_filters(): void
    {
        $puskesmas = HealthFacility::factory()->puskesmas()->create();
        $posyandu = HealthFacility::factory()->create(['parent_id' => $puskesmas->id]);
        $nakes = User::factory()->tenagaKesehatan()->create(['facility_id' => $puskesmas->id]);
        $child = Child::factory()->create([
            'facility_id' => $posyandu->id,
            'full_name' => 'Bilal Akbar',
        ]);
        RiskAssessment::factory()->high()->create([
            'child_id' => $child->id,
            'reason' => 'Berat badan turun dalam dua kunjungan terakhir.',
        ]);

        $this->actingAs($nakes)->get('/tenaga-kesehatan/peninjauan')
            ->assertOk()
            ->assertSee('Daftar peninjauan')
            ->assertSee('Bilal Akbar')
            ->assertSee('Berat badan turun dalam dua kunjungan terakhir.')
            ->assertSee('Keparahan')
            ->assertSee('Status penyelesaian');

        $this->actingAs($nakes)->get('/tenaga-kesehatan/peninjauan?severity=high&status=open')
            ->assertOk()
            ->assertSee('Bilal Akbar');
    }

    public function test_health_worker_review_queue_hides_other_puskesmas(): void
    {
        $puskesmas = HealthFacility::factory()->puskesmas()->create();
        $otherPuskesmas = HealthFacility::factory()->puskesmas()->create();
        $nakes = User::factory()->tenagaKesehatan()->create(['facility_id' => $puskesmas->id]);
        $outsider = Child::factory()->create([
            'facility_id' => $otherPuskesmas->id,
            'full_name' => 'Anak Wilayah Lain',
        ]);
        RiskAssessment::factory()->create([
            'child_id' => $outsider->id,
            'reason' => 'Temuan dari wilayah lain.',
        ]);

        $this->actingAs($nakes)->get('/tenaga-kesehatan/peninjauan')
            ->assertOk()
            ->assertDontSee('Anak Wilayah Lain')
            ->assertDontSee('Temuan dari wilayah lain.');
    }

    public function test_health_worker_can_open_analytics_page(): void
    {
        $puskesmas = HealthFacility::factory()->puskesmas()->create();
        $posyandu = HealthFacility::factory()->create(['parent_id' => $puskesmas->id]);
        $nakes = User::factory()->tenagaKesehatan()->create(['facility_id' => $puskesmas->id]);
        Child::factory()->create(['facility_id' => $posyandu->id]);

        $this->actingAs($nakes)->get('/tenaga-kesehatan/analisis')
            ->assertOk()
            ->assertSee('Hasil analisis wilayah')
            ->assertSee('Telaah per Posyandu')
            ->assertSee('Telaah klinis')
            ->assertSee($posyandu->name);
    }

    public function test_puskesmas_admin_can_open_posyandu_users_and_settings(): void
    {
        $puskesmas = HealthFacility::factory()->puskesmas()->create(['name' => 'Puskesmas Sukamaju']);
        $posyandu = HealthFacility::factory()->create([
            'name' => 'Posyandu Melati 01',
            'parent_id' => $puskesmas->id,
        ]);
        $admin = User::factory()->adminPuskesmas()->create(['facility_id' => $puskesmas->id]);
        $kader = User::factory()->kader()->create([
            'facility_id' => $posyandu->id,
            'name' => 'Kader Melati Satu',
        ]);
        Child::factory()->count(2)->create(['facility_id' => $posyandu->id]);

        $this->actingAs($admin)->get('/puskesmas/posyandu')
            ->assertOk()
            ->assertSee('Direktori Posyandu')
            ->assertSee('Posyandu Melati 01')
            ->assertSee('Sasaran balita')
            ->assertSee('Kader aktif')
            ->assertSee('Jadwal penimbangan');

        $this->actingAs($admin)->get('/puskesmas/pengguna')
            ->assertOk()
            ->assertSee('Pengguna wilayah')
            ->assertSee($kader->name)
            ->assertSee('Kader Posyandu')
            ->assertSee('Tenaga kesehatan');

        $this->actingAs($admin)->get('/puskesmas/pengaturan')
            ->assertOk()
            ->assertSee('Pengaturan wilayah')
            ->assertSee('Profil faskes')
            ->assertSee('Target pemantauan')
            ->assertSee('Kontak darurat rujukan')
            ->assertSee('Puskesmas Sukamaju');
    }

    public function test_puskesmas_user_list_can_be_filtered_by_role(): void
    {
        $puskesmas = HealthFacility::factory()->puskesmas()->create();
        $admin = User::factory()->adminPuskesmas()->create(['facility_id' => $puskesmas->id]);
        $kader = User::factory()->kader()->create(['facility_id' => $puskesmas->id, 'name' => 'Kader Satu']);
        $nakes = User::factory()->tenagaKesehatan()->create(['facility_id' => $puskesmas->id, 'name' => 'Dokter Dua']);

        $this->actingAs($admin)->get('/puskesmas/pengguna?role=kader')
            ->assertOk()
            ->assertSee($kader->name)
            ->assertDontSee($nakes->name);
    }

    public function test_users_cannot_open_another_role_dedicated_page(): void
    {
        $kader = User::factory()->kader()->create();
        $parent = User::factory()->orangTua()->create();
        $nakes = User::factory()->tenagaKesehatan()->create();
        $admin = User::factory()->adminPuskesmas()->create();

        $this->actingAs($kader)->get('/tenaga-kesehatan/peninjauan')->assertForbidden();
        $this->actingAs($kader)->get('/puskesmas/pengaturan')->assertForbidden();
        $this->actingAs($parent)->get('/kader/pengukuran')->assertForbidden();
        $this->actingAs($nakes)->get('/orang-tua/jadwal')->assertForbidden();
        $this->actingAs($admin)->get('/kader/analisis')->assertForbidden();
    }

    public function test_dedicated_pages_do_not_expose_technical_jargon(): void
    {
        $puskesmas = HealthFacility::factory()->puskesmas()->create();
        $posyandu = HealthFacility::factory()->create(['parent_id' => $puskesmas->id]);
        $admin = User::factory()->adminPuskesmas()->create(['facility_id' => $puskesmas->id]);
        Child::factory()->create(['facility_id' => $posyandu->id]);

        $jargon = ['PostgreSQL', 'JWT', 'Box-Cox', 'LMS', 'Endpoint', 'endpoint'];

        foreach (['/puskesmas/posyandu', '/puskesmas/pengguna', '/puskesmas/pengaturan'] as $route) {
            $response = $this->actingAs($admin)->get($route);

            $response->assertOk();

            foreach ($jargon as $word) {
                $response->assertDontSee($word);
            }
        }
    }
}
