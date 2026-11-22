<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\GrowthAssessment;
use App\Models\NutritionKnowledge;
use App\Services\NutritionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NutritionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_infant_under_six_months_is_guided_to_exclusive_breastfeeding(): void
    {
        $child = Child::factory()->agedMonths(3)->create();

        $recommendation = app(NutritionService::class)->recommendForChild($child);

        $this->assertSame('0-5', $recommendation['age_group']);
        $this->assertSame('ASI eksklusif dan pemantauan tumbuh kembang', $recommendation['headline']);
        $this->assertSame([], $recommendation['foods']);
        $this->assertStringContainsString('ASI eksklusif', $recommendation['summary']);
    }

    public function test_wasting_focus_prioritises_energy_and_protein_foods(): void
    {
        $child = Child::factory()->agedMonths(15)->create();
        $assessment = new GrowthAssessment([
            'wfa_status' => 'Gizi Kurang',
            'lhfa_status' => 'Normal',
            'wfh_status' => 'Gizi Kurang (Wasting)',
            'bfa_status' => 'Gizi Kurang (Wasting)',
        ]);

        $recommendation = app(NutritionService::class)->recommendForChild($child, $assessment);

        $this->assertSame('wasting', $recommendation['focus']);
        $this->assertStringContainsString('rujukan', $recommendation['headline']);
        $this->assertContains('Protein hewani', $recommendation['nutrients']);

        $tags = array_merge(...array_map(
            fn (array $food): array => $food['tags'],
            $recommendation['foods'],
        ));
        $this->assertContains('protein', $tags);
        $this->assertContains('energi', $tags);
    }

    public function test_stunting_focus_recommends_growth_supporting_nutrients(): void
    {
        $child = Child::factory()->agedMonths(30)->create();
        $assessment = new GrowthAssessment([
            'wfa_status' => 'Gizi Baik',
            'lhfa_status' => 'Pendek',
            'wfh_status' => 'Normal',
            'bfa_status' => 'Normal',
        ]);

        $recommendation = app(NutritionService::class)->recommendForChild($child, $assessment);

        $this->assertSame('pendek', $recommendation['focus']);
        $this->assertContains('Zat besi', $recommendation['nutrients']);
        $this->assertContains('Kalsium', $recommendation['nutrients']);
    }

    public function test_foods_are_never_offered_below_their_minimum_age(): void
    {
        $child = Child::factory()->agedMonths(7)->create();

        $recommendation = app(NutritionService::class)->recommendForChild($child);

        foreach ($recommendation['foods'] as $food) {
            $this->assertLessThanOrEqual(7, $food['min_age_months']);
        }
    }

    public function test_every_age_group_maps_to_a_readable_label(): void
    {
        $service = app(NutritionService::class);
        $expected = [
            0 => '0-5',
            6 => '6-8',
            9 => '9-11',
            12 => '12-23',
            24 => '24-59',
            59 => '24-59',
        ];

        foreach ($expected as $months => $group) {
            $this->assertSame($group, $service->ageGroupFor($months));
            $this->assertNotSame('', $service->ageGroupLabel($group));
        }
    }

    public function test_recommendation_surfaces_curated_knowledge_for_the_age_group(): void
    {
        NutritionKnowledge::factory()->create(['age_group' => '12-23', 'title' => 'Makan bersama keluarga']);
        $child = Child::factory()->agedMonths(18)->create();

        $recommendation = app(NutritionService::class)->recommendForChild($child);

        $this->assertTrue($recommendation['knowledge']->contains('title', 'Makan bersama keluarga'));
    }

    public function test_recommendation_avoids_therapy_or_prescription_claims(): void
    {
        $child = Child::factory()->agedMonths(15)->create();

        $recommendation = app(NutritionService::class)->recommendForChild($child);
        $text = implode(' ', [$recommendation['summary'], ...$recommendation['tips']]);

        $this->assertStringContainsString('bukan pengganti pemeriksaan medis', $text);
        $this->assertStringNotContainsString('resep', $text);
    }
}
