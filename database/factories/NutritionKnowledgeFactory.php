<?php

namespace Database\Factories;

use App\Models\NutritionKnowledge;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NutritionKnowledge>
 */
class NutritionKnowledgeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'age_group' => '12-23',
            'title' => 'Menu seimbang untuk balita',
            'content' => 'Berikan makan utama tiga kali sehari dengan variasi lauk protein hewani dan sayur.',
            'local_food_examples' => ['Telur', 'Ikan kembung', 'Daun kelor'],
            'key_nutrients' => ['Protein', 'Zat besi', 'Vitamin A'],
            'source' => 'Kementerian Kesehatan RI',
        ];
    }
}
