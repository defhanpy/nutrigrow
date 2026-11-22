<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Child;
use App\Models\GrowthAssessment;
use App\Models\HealthFacility;
use App\Models\Measurement;
use App\Models\NutritionKnowledge;
use App\Models\RiskAssessment;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_it_seeds_the_four_facilities_with_a_puskesmas_parent(): void
    {
        $this->assertDatabaseCount('health_facilities', 4);

        $puskesmas = HealthFacility::query()->where('code', 'PKM-SKM-01')->sole();
        $this->assertSame('Puskesmas Sukamaju', $puskesmas->name);
        $this->assertSame(3, $puskesmas->children()->count());
    }

    public function test_it_seeds_the_four_demo_accounts_with_working_credentials(): void
    {
        $accounts = [
            'kader@nutrigrow.id' => Role::Kader,
            'ortu@nutrigrow.id' => Role::OrangTua,
            'nakes@nutrigrow.id' => Role::TenagaKesehatan,
            'admin@nutrigrow.id' => Role::AdminPuskesmas,
        ];

        foreach ($accounts as $email => $role) {
            $user = User::query()->where('email', $email)->sole();
            $this->assertSame($role, $user->role);
            $this->assertTrue(password_verify('rahasia123', $user->password));
        }
    }

    public function test_it_seeds_ten_children_with_longitudinal_history(): void
    {
        $this->assertSame(10, Child::query()->count());
        $this->assertGreaterThan(150, Measurement::query()->count());
        $this->assertSame(Measurement::query()->count(), GrowthAssessment::query()->count());

        Child::query()->each(function (Child $child): void {
            $this->assertGreaterThanOrEqual(5, $child->measurements()->count());
            $this->assertNotNull($child->latestMeasurement?->assessment);
        });
    }

    public function test_demo_data_covers_normal_faltering_stunted_and_wasted_profiles(): void
    {
        $statuses = GrowthAssessment::query()->pluck('overall_status');

        $this->assertTrue($statuses->contains('Gizi Baik'));
        $this->assertTrue($statuses->contains('Pendek') || $statuses->contains('Sangat Pendek'));
        $this->assertTrue(
            $statuses->contains('Gizi Kurang (Wasting)')
            || $statuses->contains('Gizi Buruk (Wasting Berat)'),
        );
        $this->assertDatabaseHas('risk_assessments', ['flag' => 'growth_faltering', 'status' => 'open']);
    }

    public function test_parent_account_is_linked_to_the_demo_child(): void
    {
        $parent = User::query()->where('email', 'ortu@nutrigrow.id')->sole();
        $child = Child::query()->where('registration_no', 'NG-2025-0001')->sole();

        $this->assertTrue($parent->guardianships()->where('child_id', $child->id)->exists());
    }

    public function test_it_seeds_curated_local_nutrition_knowledge(): void
    {
        $this->assertGreaterThanOrEqual(5, NutritionKnowledge::query()->count());
        $this->assertDatabaseHas('nutrition_knowledge', ['age_group' => '6-8']);
    }

    public function test_open_risk_alerts_are_available_for_the_health_worker_queue(): void
    {
        $this->assertGreaterThan(0, RiskAssessment::query()->where('status', 'open')->count());
    }

    public function test_seeding_twice_does_not_duplicate_records(): void
    {
        $children = Child::query()->count();
        $measurements = Measurement::query()->count();

        $this->seed(DatabaseSeeder::class);

        $this->assertSame($children, Child::query()->count());
        $this->assertSame($measurements, Measurement::query()->count());
    }
}
