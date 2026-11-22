<?php

namespace Database\Factories;

use App\Enums\FollowUpStatus;
use App\Models\Child;
use App\Models\FollowUpRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FollowUpRecord>
 */
class FollowUpRecordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'child_id' => Child::factory(),
            'created_by' => null,
            'category' => 'Pendampingan gizi',
            'action_plan' => 'Dampingi keluarga menyusun menu protein hewani setiap hari.',
            'target_date' => now()->addDays(14)->toDateString(),
            'status' => FollowUpStatus::Pending,
            'notes' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => FollowUpStatus::Completed]);
    }
}
