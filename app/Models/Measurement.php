<?php

namespace App\Models;

use App\Enums\MeasurementMethod;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'child_id', 'measured_by', 'measured_at', 'weight_kg',
    'height_cm', 'lila_cm', 'method', 'notes',
])]
class Measurement extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'measured_at' => 'date',
            'weight_kg' => 'decimal:2',
            'height_cm' => 'decimal:2',
            'lila_cm' => 'decimal:2',
            'method' => MeasurementMethod::class,
        ];
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'measured_by');
    }

    public function assessment(): HasOne
    {
        return $this->hasOne(GrowthAssessment::class);
    }
}
