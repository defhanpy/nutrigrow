<?php

namespace Database\Factories;

use App\Enums\Relationship;
use App\Models\Child;
use App\Models\Guardian;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Guardian>
 */
class GuardianFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->orangTua(),
            'child_id' => Child::factory(),
            'relationship' => Relationship::Ibu,
        ];
    }
}
