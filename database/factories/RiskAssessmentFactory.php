<?php

namespace Database\Factories;

use App\Enums\RiskSeverity;
use App\Enums\RiskStatus;
use App\Models\Child;
use App\Models\RiskAssessment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RiskAssessment>
 */
class RiskAssessmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'child_id' => Child::factory(),
            'measurement_id' => null,
            'flag' => 'stunting',
            'severity' => RiskSeverity::Medium,
            'reason' => 'Tinggi badan anak berada di bawah rentang usianya dan perlu pemantauan.',
            'evidence' => ['catatan' => 'Data simulasi'],
            'status' => RiskStatus::Open,
        ];
    }

    public function high(): static
    {
        return $this->state(fn (array $attributes): array => [
            'severity' => RiskSeverity::High,
            'flag' => 'wasting_severe',
        ]);
    }
}
