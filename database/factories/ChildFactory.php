<?php

namespace Database\Factories;

use App\Enums\Sex;
use App\Models\Child;
use App\Models\HealthFacility;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Child>
 */
class ChildFactory extends Factory
{
    public function definition(): array
    {
        $dateOfBirth = Carbon::today()->subMonths(fake()->numberBetween(2, 48));

        return [
            'registration_no' => 'NG-'.fake()->unique()->numerify('####-####'),
            'nik' => fake()->unique()->numerify('3201############'),
            'full_name' => fake()->name(),
            'date_of_birth' => $dateOfBirth,
            'sex' => fake()->randomElement([Sex::LakiLaki, Sex::Perempuan]),
            'facility_id' => HealthFacility::factory(),
            'birth_weight' => fake()->randomFloat(2, 2.4, 3.9),
            'birth_height' => fake()->randomFloat(1, 46, 51),
            'is_active' => true,
        ];
    }

    public function female(): static
    {
        return $this->state(fn (array $attributes): array => ['sex' => Sex::Perempuan]);
    }

    public function male(): static
    {
        return $this->state(fn (array $attributes): array => ['sex' => Sex::LakiLaki]);
    }

    public function agedMonths(int $months): static
    {
        return $this->state(fn (array $attributes): array => [
            'date_of_birth' => Carbon::today()->subMonths($months),
        ]);
    }
}
