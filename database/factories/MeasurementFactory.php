<?php

namespace Database\Factories;

use App\Enums\MeasurementMethod;
use App\Models\Child;
use App\Models\Measurement;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Measurement>
 */
class MeasurementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'child_id' => Child::factory(),
            'measured_by' => null,
            'measured_at' => Carbon::today()->subDays(fake()->numberBetween(1, 30)),
            'weight_kg' => fake()->randomFloat(2, 5, 18),
            'height_cm' => fake()->randomFloat(1, 55, 110),
            'lila_cm' => fake()->randomFloat(1, 11, 18),
            'method' => MeasurementMethod::Terlentang,
            'notes' => null,
        ];
    }

    public function standing(): static
    {
        return $this->state(fn (array $attributes): array => ['method' => MeasurementMethod::Berdiri]);
    }
}
