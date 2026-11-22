<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'age_group', 'title', 'content', 'local_food_examples', 'key_nutrients', 'source',
])]
class NutritionKnowledge extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'local_food_examples' => 'array',
            'key_nutrients' => 'array',
        ];
    }
}
