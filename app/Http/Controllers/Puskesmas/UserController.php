<?php

namespace App\Http\Controllers\Puskesmas;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ChildAccessService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private readonly ChildAccessService $access) {}

    public function __invoke(Request $request): View
    {
        $facilityIds = $this->access->puskesmasFacilityIds($request->user());
        $role = $request->string('role')->toString();
        $users = User::query()
            ->whereIn('facility_id', $facilityIds)
            ->with('facility')
            ->withCount('guardianships')
            ->when(Role::tryFrom($role) !== null, fn ($query) => $query->where('role', $role))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();
        $counts = User::query()
            ->whereIn('facility_id', $facilityIds)
            ->selectRaw('role, count(*) as aggregate')
            ->groupBy('role')
            ->pluck('aggregate', 'role');

        return view('puskesmas.users', [
            'users' => $users,
            'filters' => [
                'role' => $role,
                'roles' => Role::cases(),
            ],
            'stats' => [
                ['label' => 'Tenaga kesehatan', 'value' => (int) ($counts[Role::TenagaKesehatan->value] ?? 0), 'icon' => 'ti-stethoscope', 'tone' => 'mint'],
                ['label' => 'Kader Posyandu', 'value' => (int) ($counts[Role::Kader->value] ?? 0), 'icon' => 'ti-user-heart', 'tone' => 'sky'],
                ['label' => 'Orang tua / wali', 'value' => (int) ($counts[Role::OrangTua->value] ?? 0), 'icon' => 'ti-users', 'tone' => 'amber'],
                ['label' => 'Administrator', 'value' => (int) ($counts[Role::AdminPuskesmas->value] ?? 0), 'icon' => 'ti-user-cog', 'tone' => 'rose'],
            ],
        ]);
    }
}
