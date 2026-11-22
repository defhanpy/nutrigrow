<?php

namespace App\Http\Controllers\Kader;

use App\Http\Controllers\Controller;
use App\Models\Child;
use App\Services\ChildAccessService;
use App\Services\GrowthMonitoringService;
use App\Services\WhoGrowthEngineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class MeasurementController extends Controller
{
    public function __construct(
        private readonly ChildAccessService $access,
        private readonly GrowthMonitoringService $monitoring,
        private readonly WhoGrowthEngineService $growthEngine,
    ) {}

    public function store(Request $request, Child $child): RedirectResponse
    {
        abort_unless($this->access->canAccess($request->user(), $child), 403);

        $validated = $request->validate([
            'measured_at' => ['required', 'date', 'before_or_equal:today'],
            'weight_kg' => ['required', 'numeric', 'min:1', 'max:40'],
            'height_cm' => ['required', 'numeric', 'min:30', 'max:130'],
            'lila_cm' => ['nullable', 'numeric', 'min:5', 'max:30'],
            'method' => ['required', Rule::in(['terlentang', 'berdiri'])],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'measured_at.required' => 'Tanggal pengukuran wajib diisi.',
            'measured_at.before_or_equal' => 'Tanggal pengukuran tidak boleh melebihi hari ini.',
            'weight_kg.required' => 'Berat badan wajib diisi.',
            'weight_kg.numeric' => 'Berat badan harus berupa angka.',
            'height_cm.required' => 'Tinggi atau panjang badan wajib diisi.',
            'method.required' => 'Posisi pengukuran wajib dipilih.',
        ]);

        $this->monitoring->record($child, $validated, $request->user());
        $this->monitoring->refreshTrajectoryRisks($child);

        return redirect()
            ->route('kader.dashboard', ['child' => $child->id])
            ->with('status', 'Pengukuran '.$child->full_name.' berhasil disimpan. Hasil penilaian sudah diperbarui.');
    }

    public function preview(Request $request, Child $child): JsonResponse
    {
        abort_unless($this->access->canAccess($request->user(), $child), 403);

        $validated = $request->validate([
            'measured_at' => ['required', 'date', 'before_or_equal:today'],
            'weight_kg' => ['required', 'numeric', 'min:1', 'max:40'],
            'height_cm' => ['required', 'numeric', 'min:30', 'max:130'],
            'method' => ['required', Rule::in(['terlentang', 'berdiri'])],
        ]);

        $result = $this->growthEngine->calculateZScores(
            $child,
            (float) $validated['weight_kg'],
            (float) $validated['height_cm'],
            $validated['method'],
            Carbon::parse($validated['measured_at']),
        );

        return response()->json([
            'age_label' => $child->ageLabel(Carbon::parse($validated['measured_at'])),
            'overall_status' => $result['overall_status'],
            'indicators' => [
                ['label' => 'Berat menurut usia', 'status' => $result['wfa_status'], 'z' => $result['wfa_z']],
                ['label' => 'Tinggi menurut usia', 'status' => $result['lhfa_status'], 'z' => $result['lhfa_z']],
                ['label' => 'Berat menurut tinggi', 'status' => $result['wfh_status'], 'z' => $result['wfh_z']],
            ],
        ]);
    }
}
