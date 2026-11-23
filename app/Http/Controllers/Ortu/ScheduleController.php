<?php

namespace App\Http\Controllers\Ortu;

use App\Http\Controllers\Controller;
use App\Models\Child;
use App\Services\ChildAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function __construct(private readonly ChildAccessService $access) {}

    public function __invoke(Request $request): View
    {
        $children = $this->access->queryFor($request->user())->orderBy('full_name')->get();
        $selected = $children->firstWhere('id', $request->integer('child')) ?? $children->first();
        $nextVisit = now()->startOfMonth()->addMonth()->day(10);

        return view('ortu.schedule', [
            'children' => $children,
            'selectedChild' => $selected,
            'nextVisit' => $nextVisit,
            'visits' => $this->upcomingVisits($nextVisit),
            'calendar' => $this->monthCalendar($nextVisit->copy()->startOfMonth(), $nextVisit),
            'immunisations' => $selected !== null ? $this->immunisations($selected) : [],
            'supplements' => $selected !== null ? $this->supplements($selected, $nextVisit) : [],
        ]);
    }

    /**
     * @return array<int, array<int, array{day: int|null, is_visit: bool, is_today: bool}>>
     */
    private function monthCalendar(Carbon $month, Carbon $visitDate): array
    {
        $weeks = [];
        $cursor = $month->copy()->startOfWeek(Carbon::MONDAY);
        $lastDay = $month->copy()->endOfMonth();

        while ($cursor->lessThanOrEqualTo($lastDay) || count($weeks) === 0) {
            $week = [];

            for ($day = 0; $day < 7; $day++) {
                $current = $cursor->copy()->addDays($day);
                $week[] = [
                    'day' => $current->month === $month->month ? $current->day : null,
                    'is_visit' => $current->isSameDay($visitDate),
                    'is_today' => $current->isToday(),
                ];
            }

            $weeks[] = $week;
            $cursor->addWeek();
        }

        return $weeks;
    }

    /**
     * @return array<int, array{date: Carbon, title: string, description: string, tone: string}>
     */
    private function upcomingVisits(Carbon $nextVisit): array
    {
        $visits = [];

        for ($index = 0; $index < 3; $index++) {
            $date = $nextVisit->copy()->addMonths($index);
            $visits[] = [
                'date' => $date,
                'title' => 'Penimbangan dan pengukuran bulanan',
                'description' => 'Datang ke Posyandu antara pukul 08.00–11.00 dengan membawa Buku KIA.',
                'tone' => $index === 0 ? 'lime' : 'mint',
            ];
        }

        return $visits;
    }

    /**
     * @return array<int, array{age: string, name: string, description: string, done: bool, due: bool}>
     */
    private function immunisations(Child $child): array
    {
        $ageMonths = $child->ageInMonths();
        $schedule = [
            ['months' => 0, 'age' => 'Saat lahir', 'name' => 'Hepatitis B dan BCG', 'description' => 'Diberikan sebelum bayi pulang dari fasilitas kesehatan.'],
            ['months' => 1, 'age' => '1 bulan', 'name' => 'Polio tetes pertama', 'description' => 'Melindungi anak dari penyakit polio.'],
            ['months' => 2, 'age' => '2 bulan', 'name' => 'DPT-HB-Hib dan polio', 'description' => 'Dosis pertama untuk melindungi dari difteri, pertusis, tetanus, dan radang otak.'],
            ['months' => 4, 'age' => '4 bulan', 'name' => 'DPT-HB-Hib dan polio', 'description' => 'Dosis kedua, lanjutan perlindungan dari dosis pertama.'],
            ['months' => 6, 'age' => '6 bulan', 'name' => 'DPT-HB-Hib dan polio', 'description' => 'Dosis ketiga melengkapi perlindungan dasar.'],
            ['months' => 9, 'age' => '9 bulan', 'name' => 'Campak dan rubella', 'description' => 'Melindungi anak dari campak dan rubella.'],
            ['months' => 18, 'age' => '18 bulan', 'name' => 'DPT-HB-Hib dan campak lanjutan', 'description' => 'Penguat agar perlindungan tetap kuat.'],
        ];

        return array_map(function (array $item) use ($ageMonths): array {
            $due = $ageMonths >= $item['months'] && $ageMonths < $item['months'] + 2;

            return [
                'age' => $item['age'],
                'name' => $item['name'],
                'description' => $item['description'],
                'done' => $ageMonths >= $item['months'] + 2,
                'due' => $due,
            ];
        }, $schedule);
    }

    /**
     * @return array<int, array{name: string, schedule: string, description: string, tone: string}>
     */
    private function supplements(Child $child, Carbon $nextVisit): array
    {
        return [
            [
                'name' => 'Vitamin A',
                'schedule' => 'Setiap Februari dan Agustus',
                'description' => 'Kapsul biru untuk usia 6–11 bulan, kapsul merah untuk usia 12–59 bulan. Diberikan di Posyandu.',
                'tone' => 'amber',
            ],
            [
                'name' => 'Obat cacing',
                'schedule' => 'Setiap Februari dan Agustus',
                'description' => 'Diberikan sekali setiap enam bulan untuk anak usia 12 bulan ke atas.',
                'tone' => 'sky',
            ],
            [
                'name' => 'Penimbangan rutin',
                'schedule' => $nextVisit->translatedFormat('d F Y'),
                'description' => 'Pantau berat dan tinggi badan setiap bulan agar perubahan kecil cepat terlihat.',
                'tone' => 'mint',
            ],
        ];
    }
}
