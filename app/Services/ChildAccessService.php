<?php

namespace App\Services;

use App\Models\Child;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ChildAccessService
{
    /**
     * Children the signed-in user may see, scoped to their role and facility.
     */
    public function queryFor(User $user): Builder
    {
        $query = Child::query()->with(['facility', 'latestMeasurement.assessment']);

        if ($user->isOrangTua()) {
            return $query->whereHas('guardians', fn (Builder $guardian) => $guardian->where('user_id', $user->id));
        }

        if ($user->isKader()) {
            return $query->where('facility_id', $user->facility_id);
        }

        if ($user->isTenagaKesehatan() || $user->isAdminPuskesmas()) {
            $facilityIds = $this->puskesmasFacilityIds($user);

            return $query->whereIn('facility_id', $facilityIds);
        }

        return $query->whereRaw('1 = 0');
    }

    public function canAccess(User $user, Child $child): bool
    {
        return $this->queryFor($user)->whereKey($child->id)->exists();
    }

    public function defaultChild(User $user): ?Child
    {
        return $this->queryFor($user)
            ->where('is_active', true)
            ->orderBy('full_name')
            ->first();
    }

    /**
     * @return array<int, int>
     */
    public function puskesmasFacilityIds(User $user): array
    {
        if ($user->facility_id === null) {
            return [];
        }

        $facility = $user->facility;

        if ($facility === null) {
            return [];
        }

        if ($facility->isPuskesmas()) {
            return $facility->children()->pluck('id')->push($facility->id)->all();
        }

        return [$facility->id];
    }
}
