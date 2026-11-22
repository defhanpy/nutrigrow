<?php

namespace App\Models;

use App\Enums\RiskSeverity;
use App\Enums\RiskStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'child_id', 'measurement_id', 'flag', 'severity', 'reason', 'evidence', 'status',
])]
class RiskAssessment extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'severity' => RiskSeverity::class,
            'status' => RiskStatus::class,
            'evidence' => 'array',
        ];
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    public function measurement(): BelongsTo
    {
        return $this->belongsTo(Measurement::class);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', RiskStatus::Open);
    }

    public function severityLabel(): string
    {
        return $this->severity->label();
    }
}
