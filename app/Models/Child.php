<?php

namespace App\Models;

use App\Enums\Sex;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

#[Fillable([
    'registration_no', 'nik', 'full_name', 'date_of_birth', 'sex',
    'facility_id', 'birth_weight', 'birth_height', 'is_active',
])]
class Child extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'sex' => Sex::class,
            'birth_weight' => 'decimal:2',
            'birth_height' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(HealthFacility::class, 'facility_id');
    }

    public function guardians(): HasMany
    {
        return $this->hasMany(Guardian::class);
    }

    public function measurements(): HasMany
    {
        return $this->hasMany(Measurement::class)->orderBy('measured_at');
    }

    public function latestMeasurement(): HasOne
    {
        return $this->hasOne(Measurement::class)->latestOfMany('measured_at');
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(GrowthAssessment::class);
    }

    public function risks(): HasMany
    {
        return $this->hasMany(RiskAssessment::class);
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(FollowUpRecord::class);
    }

    public function ageInDays(?Carbon $on = null): int
    {
        $on ??= Carbon::today();

        return max(0, (int) $this->date_of_birth->startOfDay()->diffInDays($on->copy()->startOfDay()));
    }

    public function ageInMonths(?Carbon $on = null): int
    {
        return (int) floor($this->ageInDays($on) / 30.4375);
    }

    public function ageLabel(?Carbon $on = null): string
    {
        $days = $this->ageInDays($on);

        if ($days < 60) {
            return $days.' hari';
        }

        $months = (int) floor($days / 30.4375);

        if ($months < 24) {
            return $months.' bulan';
        }

        $years = intdiv($months, 12);
        $remaining = $months % 12;

        return $remaining === 0
            ? $years.' tahun'
            : $years.' tahun '.$remaining.' bulan';
    }
}
