<?php

namespace App\Services;

use App\Models\Child;
use App\Models\GrowthAssessment;
use App\Models\NutritionKnowledge;
use Illuminate\Support\Collection;

class NutritionService
{
    /**
     * Local food catalog. Each item is a plain-language recommendation,
     * not a medical prescription or individual calorie target.
     *
     * @var array<string, array{
     *     name: string,
     *     emoji: string,
     *     min_age_months: int,
     *     nutrients: array<int, string>,
     *     texture: string,
     *     why: string,
     *     serving: string,
     *     tags: array<int, string>
     * }>
     */
    private const FOODS = [
        'kelor' => [
            'name' => 'Daun kelor',
            'emoji' => '🌿',
            'min_age_months' => 6,
            'nutrients' => ['Zat besi', 'Kalsium', 'Vitamin A', 'Protein nabati'],
            'texture' => 'Cincang halus dan campur ke bubur',
            'why' => 'Mudah ditanam di pekarangan dan membantu memenuhi zat besi serta vitamin A.',
            'serving' => '1–2 sendok makan daun kelor cincang per porsi',
            'tags' => ['sayur', 'ekonomis', 'zat_besi'],
        ],
        'tempe' => [
            'name' => 'Tempe',
            'emoji' => '🫘',
            'min_age_months' => 9,
            'nutrients' => ['Protein nabati', 'Zat besi', 'Seng'],
            'texture' => 'Lumatkan atau potong kecil-kecil',
            'why' => 'Sumber protein nabati terjangkau yang mendukung pertumbuhan jaringan.',
            'serving' => '2–3 potong kecil per hari',
            'tags' => ['protein', 'ekonomis', 'zat_besi'],
        ],
        'telur' => [
            'name' => 'Telur ayam',
            'emoji' => '🥚',
            'min_age_months' => 6,
            'nutrients' => ['Protein hewani', 'Kolom', 'Vitamin B12', 'Zat besi'],
            'texture' => 'Rebus dan lumatkan, atau campur ke bubur',
            'why' => 'Protein hewani lengkap dengan harga terjangkau untuk mengejar pertumbuhan.',
            'serving' => '1 butir per hari bila tidak ada alergi',
            'tags' => ['protein', 'ekonomis'],
        ],
        'ikan_kembung' => [
            'name' => 'Ikan kembung',
            'emoji' => '🐟',
            'min_age_months' => 9,
            'nutrients' => ['Omega-3', 'Protein hewani', 'Vitamin D', 'Yodium'],
            'texture' => 'Kukus, pisahkan duri, lalu lumatkan',
            'why' => 'Ikan lokal kaya omega-3 dan protein untuk perkembangan otak serta otot.',
            'serving' => '1–2 sendok makan ikan lumat per porsi',
            'tags' => ['protein', 'omega3', 'lokal'],
        ],
        'lele' => [
            'name' => 'Ikan lele',
            'emoji' => '🐟',
            'min_age_months' => 9,
            'nutrients' => ['Protein hewani', 'Omega-3', 'Vitamin D'],
            'texture' => 'Kukus atau pepes, lalu suwir halus',
            'why' => 'Mudah dibudidayakan dan menjadi protein hewani murah untuk keluarga.',
            'serving' => '1–2 sendok makan suwiran per porsi',
            'tags' => ['protein', 'omega3', 'ekonomis'],
        ],
        'hati_ayam' => [
            'name' => 'Hati ayam',
            'emoji' => '🍗',
            'min_age_months' => 9,
            'nutrients' => ['Zat besi', 'Vitamin A', 'Protein hewani'],
            'texture' => 'Rebus, lumatkan, campur ke nasi tim',
            'why' => 'Sumber zat besi hewani yang baik untuk mencegah anemia pada anak.',
            'serving' => '1–2 kali per minggu, porsi kecil',
            'tags' => ['zat_besi', 'protein'],
        ],
        'santan' => [
            'name' => 'Santan encer',
            'emoji' => '🥥',
            'min_age_months' => 12,
            'nutrients' => ['Lemak sehat', 'Kalori'],
            'texture' => 'Campurkan ke sayur atau bubur',
            'why' => 'Menambah energi pada makanan anak yang sulit naik berat badan.',
            'serving' => 'Gunakan santan encer, bukan santan kental berlebihan',
            'tags' => ['energi', 'lemak'],
        ],
        'alpukat' => [
            'name' => 'Alpukat',
            'emoji' => '🥑',
            'min_age_months' => 6,
            'nutrients' => ['Lemak sehat', 'Kalium', 'Vitamin E'],
            'texture' => 'Lumatkan langsung, tanpa gula tambahan',
            'why' => 'Padat energi dan lembut, cocok untuk anak yang nafsu makannya kecil.',
            'serving' => '2–3 sendok makan per hari',
            'tags' => ['energi', 'lemak', 'buah'],
        ],
        'ubi' => [
            'name' => 'Ubi jalar',
            'emoji' => '🍠',
            'min_age_months' => 6,
            'nutrients' => ['Karbohidrat', 'Vitamin A', 'Serat'],
            'texture' => 'Kukus dan lumatkan',
            'why' => 'Sumber energi lokal yang mudah didapat dan disukai anak.',
            'serving' => 'Setengah ubi ukuran sedang per hari',
            'tags' => ['energi', 'lokal'],
        ],
        'kacang_hijau' => [
            'name' => 'Kacang hijau',
            'emoji' => '🫛',
            'min_age_months' => 9,
            'nutrients' => ['Protein nabati', 'Zat besi', 'Serat'],
            'texture' => 'Rebus empuk dan lumatkan',
            'why' => 'Bahan lokal kaya protein dan zat besi untuk variasi menu.',
            'serving' => '2–3 sendok makan bubur kacang hijau tanpa gula berlebih',
            'tags' => ['protein', 'zat_besi', 'ekonomis'],
        ],
        'bayam' => [
            'name' => 'Bayam',
            'emoji' => '🥬',
            'min_age_months' => 6,
            'nutrients' => ['Zat besi', 'Folat', 'Vitamin A'],
            'texture' => 'Cincang halus dan masukkan ke bubur',
            'why' => 'Sayuran murah yang membantu melengkapi zat besi harian.',
            'serving' => '1–2 sendok makan cincangan per porsi',
            'tags' => ['sayur', 'zat_besi', 'ekonomis'],
        ],
    ];

    /**
     * @return array{
     *     age_group: string,
     *     age_label: string,
     *     focus: string,
     *     headline: string,
     *     summary: string,
     *     foods: array<int, array<string, mixed>>,
     *     nutrients: array<int, string>,
     *     tips: array<int, string>,
     *     knowledge: array<int, array<string, mixed>>
     * }
     */
    public function recommendForChild(Child $child, ?GrowthAssessment $assessment = null): array
    {
        $ageMonths = $child->ageInMonths();
        $ageGroup = $this->ageGroupFor($ageMonths);
        $focus = $this->focusFor($assessment);

        return [
            'age_group' => $ageGroup,
            'age_label' => $this->ageGroupLabel($ageGroup),
            'focus' => $focus,
            'headline' => $this->headlineFor($focus, $ageGroup),
            'summary' => $this->summaryFor($focus, $ageMonths),
            'foods' => $this->foodsFor($ageMonths, $focus),
            'nutrients' => $this->nutrientsFor($focus),
            'tips' => $this->tipsFor($ageGroup, $focus),
            'knowledge' => $this->knowledgeFor($ageGroup),
        ];
    }

    /**
     * @return Collection<int, NutritionKnowledge>
     */
    public function knowledgeFor(string $ageGroup): Collection
    {
        return NutritionKnowledge::query()
            ->where('age_group', $ageGroup)
            ->orderBy('title')
            ->get();
    }

    public function ageGroupFor(int $ageMonths): string
    {
        return match (true) {
            $ageMonths < 6 => '0-5',
            $ageMonths < 9 => '6-8',
            $ageMonths < 12 => '9-11',
            $ageMonths < 24 => '12-23',
            default => '24-59',
        };
    }

    public function ageGroupLabel(string $ageGroup): string
    {
        return match ($ageGroup) {
            '0-5' => '0–5 bulan',
            '6-8' => '6–8 bulan',
            '9-11' => '9–11 bulan',
            '12-23' => '12–23 bulan',
            default => '24–59 bulan',
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function foodsFor(int $ageMonths, string $focus): array
    {
        $priorityTags = match ($focus) {
            'wasting', 'kurang_berat' => ['protein', 'energi', 'zat_besi'],
            'pendek' => ['protein', 'zat_besi', 'sayur'],
            'risiko_lebih' => ['sayur', 'buah'],
            default => ['protein', 'sayur', 'energi'],
        };

        $energyFoods = ['santan', 'alpukat', 'ubi'];

        $eligible = collect(self::FOODS)
            ->filter(fn (array $food) => $food['min_age_months'] <= $ageMonths)
            ->map(function (array $food, string $slug) use ($priorityTags, $focus, $energyFoods): array {
                $score = count(array_intersect($food['tags'], $priorityTags));

                if (in_array($focus, ['wasting', 'kurang_berat'], true) && in_array($slug, $energyFoods, true)) {
                    $score++;
                }

                return [
                    'slug' => $slug,
                    ...$food,
                    'score' => $score,
                ];
            })
            ->sortByDesc('score')
            ->values();

        return $eligible->take(6)->all();
    }

    private function focusFor(?GrowthAssessment $assessment): string
    {
        if ($assessment === null) {
            return 'seimbang';
        }

        $statuses = [
            $assessment->wfa_status,
            $assessment->lhfa_status,
            $assessment->wfh_status,
            $assessment->bfa_status,
        ];

        if ($this->containsAny($statuses, ['Gizi Buruk (Wasting Berat)', 'Gizi Kurang (Wasting)'])) {
            return 'wasting';
        }

        if ($this->containsAny($statuses, ['Sangat Pendek', 'Pendek'])) {
            return 'pendek';
        }

        if ($this->containsAny($statuses, ['Gizi Buruk', 'Gizi Kurang'])) {
            return 'kurang_berat';
        }

        if ($this->containsAny($statuses, ['Gizi Lebih', 'Berisiko Gizi Lebih'])) {
            return 'risiko_lebih';
        }

        return 'seimbang';
    }

    private function headlineFor(string $focus, string $ageGroup): string
    {
        if ($ageGroup === '0-5') {
            return 'ASI eksklusif dan pemantauan tumbuh kembang';
        }

        return match ($focus) {
            'wasting' => 'Prioritas: pemulihan gizi dan rujukan tenaga kesehatan',
            'kurang_berat' => 'Fokus pada makanan padat energi dan protein',
            'pendek' => 'Dukung pertumbuhan dengan protein dan zat besi cukup',
            'risiko_lebih' => 'Jaga keseimbangan porsi dan aktivitas anak',
            default => 'Menu seimbang dari bahan pangan lokal',
        };
    }

    private function summaryFor(string $focus, int $ageMonths): string
    {
        if ($ageMonths < 6) {
            return 'Pada usia ini, ASI eksklusif adalah sumber gizi utama. Belum perlu memberikan makanan atau minuman tambahan tanpa arahan tenaga kesehatan.';
        }

        return match ($focus) {
            'wasting' => 'Hasil pengukuran menunjukkan berat badan yang perlu segera ditinjau. Lengkapi menu padat gizi, lalu konsultasikan ke tenaga kesehatan untuk pemeriksaan lebih lanjut. Rekomendasi ini bukan pengganti pemeriksaan medis.',
            'kurang_berat' => 'Berat badan anak berada di bawah rentang yang diharapkan. Tambahkan protein hewani dan makanan padat energi dalam porsi kecil namun sering, serta jadwalkan penimbangan ulang. Rekomendasi ini bukan pengganti pemeriksaan medis.',
            'pendek' => 'Tinggi badan anak perlu dipantau. Pastikan asupan protein, zat besi, dan vitamin A cukup, serta lanjutkan pemantauan rutin setiap bulan. Rekomendasi ini bukan pengganti pemeriksaan medis.',
            'risiko_lebih' => 'Berat badan anak berada di atas rentang yang diharapkan. Tetap berikan menu bergizi seimbang, kurangi makanan manis kemasan, dan ajak anak bergerak aktif. Rekomendasi ini bukan pengganti pemeriksaan medis.',
            default => 'Pertumbuhan anak berada dalam rentang baik. Pertahankan variasi menu lokal dan datang rutin ke Posyandu setiap bulan. Rekomendasi ini bukan pengganti pemeriksaan medis.',
        };
    }

    /**
     * @return array<int, string>
     */
    private function nutrientsFor(string $focus): array
    {
        return match ($focus) {
            'wasting', 'kurang_berat' => ['Protein hewani', 'Energi', 'Zat besi', 'Seng', 'Vitamin A'],
            'pendek' => ['Protein hewani', 'Zat besi', 'Kalsium', 'Vitamin A', 'Vitamin D'],
            'risiko_lebih' => ['Serat', 'Protein seimbang', 'Vitamin', 'Air putih'],
            default => ['Protein', 'Karbohidrat', 'Sayur dan buah', 'Zat besi'],
        };
    }

    /**
     * @return array<int, string>
     */
    private function tipsFor(string $ageGroup, string $focus): array
    {
        $base = match ($ageGroup) {
            '0-5' => [
                'Berikan ASI sesuai kebutuhan, siang maupun malam.',
                'Lengkapi dengan imunisasi sesuai jadwal Posyandu.',
                'Timbang dan ukur panjang badan setiap bulan.',
            ],
            '6-8' => [
                'Mulai MPASI dengan tekstur lumat, lalu naik bertahap ke cincang.',
                'Berikan 2–3 kali makan utama ditambah ASI.',
                'Kenalkan satu bahan baru setiap 2–3 hari untuk melihat alergi.',
            ],
            '9-11' => [
                'Berikan 3 kali makan utama dan 1–2 kali selingan bergizi.',
                'Variasikan protein hewani dan nabati setiap hari.',
                'Latih anak memegang makanan kecil untuk melatih motorik.',
            ],
            '12-23' => [
                'Berikan 3 kali makan utama dan 2 kali selingan.',
                'Ajak anak makan bersama keluarga agar mau mencoba menu baru.',
                'Batasi makanan manis dan minuman kemasan.',
            ],
            default => [
                'Berikan porsi kecil tapi sering, 3 kali makan dan 2 kali selingan.',
                'Libatkan anak memilih sayur dan buah favoritnya.',
                'Pastikan anak cukup tidur dan bergerak aktif setiap hari.',
            ],
        };

        if (in_array($focus, ['wasting', 'kurang_berat'], true)) {
            $base[] = 'Tambahkan minyak atau santan encer ke makanan untuk menambah energi.';
            $base[] = 'Catat makanan harian anak selama seminggu untuk dibahas bersama kader.';
        }

        if ($focus === 'pendek') {
            $base[] = 'Pastikan anak mendapat paparan sinar matahari pagi dan protein hewani setiap hari.';
        }

        return $base;
    }

    /**
     * @param  array<int, string|null>  $statuses
     * @param  array<int, string>  $needles
     */
    private function containsAny(array $statuses, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (in_array($needle, $statuses, true)) {
                return true;
            }
        }

        return false;
    }
}
