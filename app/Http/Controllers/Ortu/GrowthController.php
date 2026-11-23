<?php

namespace App\Http\Controllers\Ortu;

use App\Http\Controllers\Controller;
use App\Models\Child;
use App\Services\ChildAccessService;
use App\Services\GrowthChartService;
use App\Services\WhoGrowthEngineService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GrowthController extends Controller
{
    public function __construct(
        private readonly ChildAccessService $access,
        private readonly GrowthChartService $charts,
        private readonly WhoGrowthEngineService $growthEngine,
    ) {}

    public function __invoke(Request $request): View
    {
        $children = $this->access->queryFor($request->user())
            ->withCount('measurements')
            ->orderBy('full_name')
            ->get();
        $selected = $children->firstWhere('id', $request->integer('child')) ?? $children->first();
        $measurements = $selected?->measurements()
            ->with('assessment')
            ->latest('measured_at')
            ->paginate(10)
            ->withQueryString() ?? new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);

        return view('ortu.growth', [
            'children' => $children,
            'selectedChild' => $selected,
            'chart' => $selected !== null ? $this->charts->weightChart($selected) : null,
            'trajectory' => $selected !== null ? $this->growthEngine->evaluateTrajectory($selected) : null,
            'measurements' => $measurements,
            'latestAssessment' => $selected?->latestMeasurement?->assessment,
            'milestones' => $selected !== null ? $this->milestones($selected) : [],
        ]);
    }

    /**
     * Age milestones written for families, not clinicians.
     *
     * @return array<int, array{age: string, title: string, description: string, reached: bool}>
     */
    private function milestones(Child $child): array
    {
        $ageMonths = $child->ageInMonths();
        $milestones = [
            ['age' => '0–6 bulan', 'months' => 0, 'title' => 'ASI eksklusif', 'description' => 'Bayi hanya diberi ASI, lalu mulai belajar menyusu dengan baik.'],
            ['age' => '6 bulan', 'months' => 6, 'title' => 'Makanan pendamping pertama', 'description' => 'Mulai makanan lumat dari bahan lokal seperti bubur beras dan sayur.'],
            ['age' => '9 bulan', 'months' => 9, 'title' => 'Tekstur makanan naik', 'description' => 'Anak mulai belajar makanan cincang dan protein hewani setiap hari.'],
            ['age' => '12 bulan', 'months' => 12, 'title' => 'Makan menu keluarga', 'description' => 'Tiga kali makan utama dengan tekstur yang disesuaikan.'],
            ['age' => '18 bulan', 'months' => 18, 'title' => 'Belajar makan mandiri', 'description' => 'Anak mulai memegang sendok dan memilih makanan favoritnya.'],
            ['age' => '24 bulan', 'months' => 24, 'title' => 'Balita aktif', 'description' => 'Menu seimbang dengan variasi sayur, buah, dan protein setiap hari.'],
        ];

        return array_map(fn (array $milestone): array => [
            'age' => $milestone['age'],
            'title' => $milestone['title'],
            'description' => $milestone['description'],
            'reached' => $ageMonths >= $milestone['months'],
        ], $milestones);
    }
}
