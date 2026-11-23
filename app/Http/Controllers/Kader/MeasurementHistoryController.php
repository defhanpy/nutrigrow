<?php

namespace App\Http\Controllers\Kader;

use App\Http\Controllers\Controller;
use App\Models\Child;
use App\Models\Measurement;
use App\Services\ChildAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class MeasurementHistoryController extends Controller
{
    public function __construct(private readonly ChildAccessService $access) {}

    public function __invoke(Request $request): View
    {
        $children = $this->access->queryFor($request->user())->orderBy('full_name')->get();
        $selected = $children->firstWhere('id', $request->integer('child'));
        $measurements = Measurement::query()
            ->with(['child', 'assessment', 'recorder'])
            ->whereIn('child_id', $children->pluck('id'))
            ->when($selected !== null, fn ($query) => $query->where('child_id', $selected->id))
            ->when($request->filled('month'), function ($query) use ($request): void {
                $month = Carbon::createFromFormat('Y-m', $request->string('month')->toString());
                $query->whereYear('measured_at', $month->year)->whereMonth('measured_at', $month->month);
            })
            ->latest('measured_at')
            ->paginate(15)
            ->withQueryString();
        $measuredThisMonth = Measurement::query()
            ->whereIn('child_id', $children->pluck('id'))
            ->whereBetween('measured_at', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->distinct('child_id')
            ->count('child_id');
        $totalMeasurements = Measurement::query()->whereIn('child_id', $children->pluck('id'))->count();
        $childrenWithoutVisit = $children->filter(
            fn (Child $child): bool => $child->latestMeasurement === null
                || $child->latestMeasurement->measured_at->lt(now()->startOfMonth()),
        )->count();

        return view('kader.measurements', [
            'children' => $children,
            'selectedChild' => $selected,
            'measurements' => $measurements,
            'stats' => [
                ['label' => 'Balita terdaftar', 'value' => $children->count(), 'icon' => 'ti-users', 'tone' => 'mint'],
                ['label' => 'Ditimbang bulan ini', 'value' => $measuredThisMonth, 'icon' => 'ti-scale', 'tone' => 'sky'],
                ['label' => 'Belum ditimbang bulan ini', 'value' => $childrenWithoutVisit, 'icon' => 'ti-calendar-exclamation', 'tone' => 'amber'],
                ['label' => 'Pengukuran tercatat', 'value' => $totalMeasurements, 'icon' => 'ti-history', 'tone' => 'rose'],
            ],
        ]);
    }
}
