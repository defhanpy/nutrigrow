<?php

namespace App\Models;

use App\Enums\Role;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'facility_id', 'status'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'status' => UserStatus::class,
        ];
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(HealthFacility::class, 'facility_id');
    }

    public function guardianships(): HasMany
    {
        return $this->hasMany(Guardian::class);
    }

    public function measuredChildren(): HasMany
    {
        return $this->hasMany(Measurement::class, 'measured_by');
    }

    public function createdFollowUps(): HasMany
    {
        return $this->hasMany(FollowUpRecord::class, 'created_by');
    }

    public function roleLabel(): string
    {
        return $this->role->label();
    }

    public function isKader(): bool
    {
        return $this->role === Role::Kader;
    }

    public function isOrangTua(): bool
    {
        return $this->role === Role::OrangTua;
    }

    public function isTenagaKesehatan(): bool
    {
        return $this->role === Role::TenagaKesehatan;
    }

    public function isAdminPuskesmas(): bool
    {
        return $this->role === Role::AdminPuskesmas;
    }

    public function homeRoute(): string
    {
        return $this->role->dashboardRoute();
    }

    public function initials(): string
    {
        $words = preg_split('/\s+/', trim($this->name)) ?: [];
        $initials = '';

        foreach (array_slice($words, 0, 2) as $word) {
            $initials .= mb_strtoupper(mb_substr($word, 0, 1));
        }

        return $initials !== '' ? $initials : 'NG';
    }
}
