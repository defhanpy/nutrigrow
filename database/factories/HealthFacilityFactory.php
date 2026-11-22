<?php

namespace Database\Factories;

use App\Enums\FacilityType;
use App\Models\HealthFacility;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HealthFacility>
 */
class HealthFacilityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Posyandu '.fake()->unique()->city(),
            'type' => FacilityType::Posyandu,
            'code' => 'PSY-'.fake()->unique()->numerify('###'),
            'address' => fake()->address(),
            'parent_id' => null,
        ];
    }

    public function puskesmas(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'Puskesmas '.fake()->unique()->city(),
            'type' => FacilityType::Puskesmas,
            'code' => 'PKM-'.fake()->unique()->numerify('###'),
        ]);
    }
}
