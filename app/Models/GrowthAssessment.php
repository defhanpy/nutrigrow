<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'measurement_id', 'child_id', 'age_in_days', 'wfa_z', 'lhfa_z', 'wfh_z', 'bfa_z',
    'wfa_status', 'lhfa_status', 'wfh_status', 'bfa_status', 'overall_status',
])]
class GrowthAssessment extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'age_in_days' => 'integer',
            'wfa_z' => 'decimal:2',
            'lhfa_z' => 'decimal:2',
            'wfh_z' => 'decimal:2',
            'bfa_z' => 'decimal:2',
        ];
    }

    public function measurement(): BelongsTo
    {
        return $this->belongsTo(Measurement::class);
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    public function hasConcern(): bool
    {
        return collect([$this->wfa_status, $this->lhfa_status, $this->wfh_status])
            ->contains(fn (?string $status) => $status !== null && ! in_array($status, [
                'Gizi Baik', 'Normal', 'Tinggi', 'Berisiko Gizi Lebih',
            ], true));
    }
}
