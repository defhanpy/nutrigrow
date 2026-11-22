<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\HealthFacility;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => Role::Kader,
            'facility_id' => HealthFacility::factory(),
            'status' => UserStatus::Active,
        ];
    }

    public function role(Role $role): static
    {
        return $this->state(fn (array $attributes): array => ['role' => $role]);
    }

    public function kader(): static
    {
        return $this->role(Role::Kader);
    }

    public function orangTua(): static
    {
        return $this->role(Role::OrangTua);
    }

    public function tenagaKesehatan(): static
    {
        return $this->role(Role::TenagaKesehatan);
    }

    public function adminPuskesmas(): static
    {
        return $this->role(Role::AdminPuskesmas);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => UserStatus::Inactive]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
