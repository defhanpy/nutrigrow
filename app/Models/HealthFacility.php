<?php

namespace App\Models;

use App\Enums\FacilityType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'type', 'code', 'address', 'parent_id'])]
class HealthFacility extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => FacilityType::class,
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'facility_id');
    }

    public function childProfiles(): HasMany
    {
        return $this->hasMany(Child::class, 'facility_id');
    }

    public function isPuskesmas(): bool
    {
        return $this->type === FacilityType::Puskesmas;
    }

    public function scopePosyandu($query)
    {
        return $query->where('type', FacilityType::Posyandu);
    }
}
