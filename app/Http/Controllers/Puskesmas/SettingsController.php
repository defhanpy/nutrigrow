<?php

namespace App\Http\Controllers\Puskesmas;

use App\Http\Controllers\Controller;
use App\Models\HealthFacility;
use App\Models\User;
use App\Services\ChildAccessService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(private readonly ChildAccessService $access) {}

    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $facilityIds = $this->access->puskesmasFacilityIds($user);
        $facility = $user->facility;
        $posyandu = HealthFacility::query()
            ->whereIn('id', $facilityIds)
            ->where('type', 'posyandu')
            ->orderBy('name')
            ->get();
        $team = User::query()
            ->whereIn('facility_id', $facilityIds)
            ->whereIn('role', ['kader', 'nakes', 'admin_puskesmas'])
            ->with('facility')
            ->orderBy('name')
            ->get();

        return view('puskesmas.settings', [
            'facility' => $facility,
            'posyandu' => $posyandu,
            'team' => $team,
            'targets' => [
                ['label' => 'Target cakupan D/S', 'value' => '80%', 'description' => 'Minimal delapan dari sepuluh balita ditimbang setiap bulan.'],
                ['label' => 'Batas tindak lanjut wasting', 'value' => '3 hari kerja', 'description' => 'Tanda gizi kurang akut ditindaklanjuti sebelum akhir pekan.'],
                ['label' => 'Pengukuran minimal', 'value' => '1 kali/bulan', 'description' => 'Setiap balita memiliki setidaknya satu catatan ukur per bulan.'],
            ],
            'contacts' => [
                ['label' => 'Puskesmas', 'name' => $facility?->name ?? 'Puskesmas Sukamaju', 'detail' => $facility?->address ?? 'Jl. Raya Sukamaju No. 12'],
                ['label' => 'Rujukan gizi', 'name' => 'Poli Gizi Puskesmas', 'detail' => 'Buka Senin–Jumat, 08.00–14.00'],
                ['label' => 'Rujukan darurat', 'name' => 'IGD Puskesmas', 'detail' => 'Layanan 24 jam untuk kondisi gawat'],
            ],
        ]);
    }
}
