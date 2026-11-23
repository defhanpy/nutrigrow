<?php

namespace App\Http\Controllers\Puskesmas;

use App\Http\Controllers\Controller;
use App\Models\HealthFacility;
use App\Models\Measurement;
use App\Services\ChildAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class PosyanduController extends Controller
{
    public function __construct(private readonly ChildAccessService $access) {}

    public function __invoke(Request $request): View
    {
        $facilityIds = $this->access->puskesmasFacilityIds($request->user());
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();
        $posyandu = HealthFacility::query()
            ->whereIn('id', $facilityIds)
            ->where('type', 'posyandu')
            ->withCount([
                'childProfiles as total_target' => fn ($query) => $query->where('is_active', true),
                'users as active_kader' => fn ($query) => $query->where('role', 'kader')->where('status', 'active'),
            ])
            ->orderBy('name')
            ->get()
            ->map(function (HealthFacility $facility) use ($monthStart, $monthEnd): array {
                $childIds = $facility->childProfiles()->where('is_active', true)->pluck('id');
                $measured = Measurement::query()
                    ->whereIn('child_id', $childIds)
                    ->whereBetween('measured_at', [$monthStart->toDateString(), $monthEnd->toDateString()])
                    ->distinct('child_id')
                    ->count('child_id');

                return [
                    'facility' => $facility,
                    'total_target' => $facility->total_target,
                    'active_kader' => $facility->active_kader,
                    'measured_this_month' => $measured,
                    'coverage_percent' => $facility->total_target > 0
                        ? round($measured / $facility->total_target * 100, 1)
                        : 0.0,
                    'schedule' => $this->scheduleLabel($facility),
                ];
            });

        return view('puskesmas.posyandu', [
            'posyandu' => $posyandu,
            'stats' => [
                ['label' => 'Posyandu binaan', 'value' => $posyandu->count(), 'icon' => 'ti-building-community', 'tone' => 'mint'],
                ['label' => 'Sasaran balita', 'value' => $posyandu->sum('total_target'), 'icon' => 'ti-users', 'tone' => 'sky'],
                ['label' => 'Kader aktif', 'value' => $posyandu->sum('active_kader'), 'icon' => 'ti-user-heart', 'tone' => 'amber'],
                ['label' => 'Ditimbang bulan ini', 'value' => $posyandu->sum('measured_this_month'), 'icon' => 'ti-scale', 'tone' => 'rose'],
            ],
        ]);
    }

    private function scheduleLabel(HealthFacility $facility): string
    {
        $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

        return 'Setiap '.$days[abs(crc32($facility->code)) % count($days)].' · 08.00–11.00';
    }
}
