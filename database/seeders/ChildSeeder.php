<?php

namespace Database\Seeders;

use App\Enums\MeasurementMethod;
use App\Enums\Relationship;
use App\Enums\Sex;
use App\Models\Child;
use App\Models\Guardian;
use App\Models\HealthFacility;
use App\Models\User;
use App\Services\GrowthMonitoringService;
use App\Services\WhoGrowthEngineService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ChildSeeder extends Seeder
{
    public function __construct(
        private readonly WhoGrowthEngineService $growthEngine,
        private readonly GrowthMonitoringService $monitoring,
    ) {}

    public function run(): void
    {
        $facilities = HealthFacility::query()->where('type', 'posyandu')->get()->keyBy('code');
        $kader = User::query()->where('email', 'kader@nutrigrow.id')->firstOrFail();
        $ortu = User::query()->where('email', 'ortu@nutrigrow.id')->firstOrFail();
        $nakes = User::query()->where('email', 'nakes@nutrigrow.id')->firstOrFail();
        $today = Carbon::today();

        $profiles = $this->profiles($today);

        foreach ($profiles as $index => $profile) {
            $facility = $facilities->get($profile['facility_code']) ?? $facilities->first();
            $child = Child::query()->updateOrCreate(
                ['registration_no' => $profile['registration_no']],
                [
                    'nik' => $profile['nik'],
                    'full_name' => $profile['name'],
                    'date_of_birth' => $profile['date_of_birth']->toDateString(),
                    'sex' => $profile['sex'],
                    'facility_id' => $facility->id,
                    'birth_weight' => $profile['birth_weight'],
                    'birth_height' => $profile['birth_height'],
                    'is_active' => true,
                ],
            );

            $child->measurements()->delete();

            foreach ($profile['visits'] as $visit) {
                $method = $visit['age_days'] < 731
                    ? MeasurementMethod::Terlentang->value
                    : MeasurementMethod::Berdiri->value;

                $this->monitoring->record($child, [
                    'measured_at' => $visit['date']->toDateString(),
                    'weight_kg' => $visit['weight_kg'],
                    'height_cm' => $visit['height_cm'],
                    'lila_cm' => $visit['lila_cm'] ?? null,
                    'method' => $method,
                    'notes' => $visit['notes'] ?? null,
                ], ($profile['recorded_by_nakes'] ?? false) ? $nakes : $kader);
            }

            $this->monitoring->refreshTrajectoryRisks($child);

            if ($profile['guardian_email'] ?? null) {
                $guardianUser = User::query()->where('email', $profile['guardian_email'])->first();
                if ($guardianUser) {
                    Guardian::query()->updateOrCreate(
                        ['user_id' => $guardianUser->id, 'child_id' => $child->id],
                        ['relationship' => $profile['relationship'] ?? Relationship::Ibu],
                    );
                }
            }

            if ($profile['link_ortu'] ?? false) {
                Guardian::query()->updateOrCreate(
                    ['user_id' => $ortu->id, 'child_id' => $child->id],
                    ['relationship' => Relationship::Ibu],
                );
            }
        }

        $this->seedFollowUps($today);
        $this->resolveOlderRisks();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function profiles(Carbon $today): array
    {
        return [
            $this->profile(
                name: 'Afiqah Zahra',
                registrationNo: 'NG-2025-0001',
                nik: '3201014501240001',
                sex: Sex::Perempuan,
                monthsOld: 21,
                facilityCode: 'PSY-MLT-01',
                birthWeight: 3.05,
                birthHeight: 48.5,
                weightZ: [0.15, 0.16, 0.17, 0.18, 0.19, 0.2, 0.21, 0.22, 0.23, 0.24, 0.25, 0.26, 0.27, 0.28, 0.29, 0.3, 0.31, 0.32, 0.33, 0.34, 0.35],
                heightZ: [0.2, 0.22, 0.24, 0.26, 0.28, 0.3, 0.32, 0.34, 0.36, 0.38, 0.4, 0.42, 0.44, 0.46, 0.48, 0.5, 0.52, 0.54, 0.56, 0.58, 0.6],
                linkOrtu: true,
                relationship: Relationship::Ibu,
            ),
            $this->profile(
                name: 'Muhammad Raka',
                registrationNo: 'NG-2025-0002',
                nik: '3201011206240002',
                sex: Sex::LakiLaki,
                monthsOld: 17,
                facilityCode: 'PSY-MLT-02',
                birthWeight: 3.35,
                birthHeight: 49.5,
                weightZ: [0.2, 0.22, 0.24, 0.26, 0.28, 0.3, 0.32, 0.34, 0.36, 0.38, 0.4, 0.42, 0.44, 0.46, 0.48, 0.5, 0.52, 0.54],
                heightZ: [0.3, 0.32, 0.34, 0.36, 0.38, 0.4, 0.42, 0.44, 0.46, 0.48, 0.5, 0.52, 0.54, 0.56, 0.58, 0.6, 0.62, 0.64],
            ),
            $this->profile(
                name: 'Bilal Akbar',
                registrationNo: 'NG-2025-0003',
                nik: '3201010808250003',
                sex: Sex::LakiLaki,
                monthsOld: 15,
                facilityCode: 'PSY-MLT-01',
                birthWeight: 3.0,
                birthHeight: 48.0,
                weightZ: [-0.4, -0.45, -0.5, -0.55, -0.6, -0.7, -0.8, -0.9, -1.0, -1.15, -1.3, -1.5, -1.7, -1.85, -2.0],
                heightZ: [-0.3, -0.35, -0.3, -0.4, -0.35, -0.4, -0.45, -0.4, -0.5, -0.45, -0.5, -0.55, -0.5, -0.55, -0.6],
                notes: ['Nafsu makan menurun sejak bulan lalu.'],
            ),
            $this->profile(
                name: 'Siti Khadijah',
                registrationNo: 'NG-2024-0004',
                nik: '3201012104230004',
                sex: Sex::Perempuan,
                monthsOld: 30,
                facilityCode: 'PSY-MLT-02',
                birthWeight: 2.8,
                birthHeight: 47.0,
                weightZ: [-0.85, -0.84, -0.83, -0.82, -0.81, -0.8, -0.79, -0.78, -0.77, -0.76, -0.75, -0.74, -0.73, -0.72, -0.71, -0.7, -0.69, -0.68, -0.67, -0.66, -0.65, -0.64, -0.63, -0.62, -0.61, -0.6, -0.59, -0.58, -0.57, -0.56],
                heightZ: [-2.7, -2.7, -2.7, -2.7, -2.7, -2.7, -2.7, -2.7, -2.7, -2.7, -2.7, -2.7, -2.7, -2.7, -2.7, -2.7, -2.7, -2.7, -2.7, -2.7, -2.7, -2.7, -2.7, -2.7, -2.7, -2.7, -2.7, -2.7, -2.7, -2.7],
            ),
            $this->profile(
                name: 'Kevin Saputra',
                registrationNo: 'NG-2025-0005',
                nik: '3201011501260005',
                sex: Sex::LakiLaki,
                monthsOld: 11,
                facilityCode: 'PSY-MLT-01',
                birthWeight: 2.7,
                birthHeight: 47.5,
                weightZ: [-1.2, -1.4, -1.6, -1.8, -2.0, -2.2, -2.4, -2.6, -2.8, -2.9, -3.0, -3.1],
                heightZ: [-0.3, -0.4, -0.35, -0.45, -0.4, -0.5, -0.45, -0.5, -0.55, -0.5, -0.55, -0.6],
                notes: ['Sudah 2 minggu batuk pilek, makan hanya sedikit.'],
                recordedByNakes: true,
            ),
            $this->profile(
                name: 'Nadia Putri',
                registrationNo: 'NG-2023-0006',
                nik: '3201010307220006',
                sex: Sex::Perempuan,
                monthsOld: 40,
                facilityCode: 'PSY-MLT-03',
                birthWeight: 3.2,
                birthHeight: 49.0,
                weightZ: [0.2, 0.21, 0.22, 0.23, 0.24, 0.25, 0.26, 0.27, 0.28, 0.29, 0.3, 0.31, 0.32, 0.33, 0.34, 0.35, 0.36, 0.37, 0.38, 0.39, 0.4, 0.41, 0.42, 0.43, 0.44, 0.45, 0.46, 0.47, 0.48, 0.49, 0.5, 0.51, 0.52, 0.53, 0.54, 0.55, 0.56, 0.57, 0.58, 0.59],
                heightZ: [0.3, 0.31, 0.32, 0.33, 0.34, 0.35, 0.36, 0.37, 0.38, 0.39, 0.4, 0.41, 0.42, 0.43, 0.44, 0.45, 0.46, 0.47, 0.48, 0.49, 0.5, 0.51, 0.52, 0.53, 0.54, 0.55, 0.56, 0.57, 0.58, 0.59, 0.6, 0.61, 0.62, 0.63, 0.64, 0.65, 0.66, 0.67, 0.68, 0.69],
            ),
            $this->profile(
                name: 'Arif Maulana',
                registrationNo: 'NG-2024-0007',
                nik: '3201011105240007',
                sex: Sex::LakiLaki,
                monthsOld: 26,
                facilityCode: 'PSY-MLT-03',
                birthWeight: 2.9,
                birthHeight: 48.0,
                weightZ: [-1.2, -1.1, -1.0, -1.0, -0.9, -0.9, -0.8, -0.8, -0.7, -0.7, -0.6, -0.6, -0.5, -0.5, -0.4, -0.4, -0.3, -0.3, -0.2, -0.2, -0.1, -0.1, 0.0, 0.0, 0.1, 0.1],
                heightZ: [-2.9, -2.85, -2.8, -2.75, -2.7, -2.65, -2.6, -2.55, -2.5, -2.45, -2.4, -2.35, -2.3, -2.25, -2.2, -2.15, -2.1, -2.05, -2.0, -1.95, -1.9, -1.85, -1.8, -1.75, -1.7, -1.65],
            ),
            $this->profile(
                name: 'Zahra Nabila',
                registrationNo: 'NG-2026-0008',
                nik: '3201012203260008',
                sex: Sex::Perempuan,
                monthsOld: 8,
                facilityCode: 'PSY-MLT-02',
                birthWeight: 3.1,
                birthHeight: 49.0,
                weightZ: [0.15, 0.16, 0.17, 0.18, 0.19, 0.2, 0.21, 0.22, 0.23],
                heightZ: [0.2, 0.21, 0.22, 0.23, 0.24, 0.25, 0.26, 0.27, 0.28],
            ),
            $this->profile(
                name: 'Fajar Ramadhan',
                registrationNo: 'NG-2022-0009',
                nik: '3201010903210009',
                sex: Sex::LakiLaki,
                monthsOld: 48,
                facilityCode: 'PSY-MLT-01',
                birthWeight: 3.4,
                birthHeight: 50.0,
                weightZ: [0.3, 0.31, 0.32, 0.33, 0.34, 0.35, 0.36, 0.37, 0.38, 0.39, 0.4, 0.41, 0.42, 0.43, 0.44, 0.45, 0.46, 0.47, 0.48, 0.49, 0.5, 0.51, 0.52, 0.53, 0.54, 0.55, 0.56, 0.57, 0.58, 0.59, 0.6, 0.61, 0.62, 0.63, 0.64, 0.65, 0.66, 0.67, 0.68, 0.69, 0.7, 0.71, 0.72, 0.73, 0.74, 0.75, 0.76, 0.77],
                heightZ: [0.4, 0.41, 0.42, 0.43, 0.44, 0.45, 0.46, 0.47, 0.48, 0.49, 0.5, 0.51, 0.52, 0.53, 0.54, 0.55, 0.56, 0.57, 0.58, 0.59, 0.6, 0.61, 0.62, 0.63, 0.64, 0.65, 0.66, 0.67, 0.68, 0.69, 0.7, 0.71, 0.72, 0.73, 0.74, 0.75, 0.76, 0.77, 0.78, 0.79, 0.8, 0.81, 0.82, 0.83, 0.84, 0.85, 0.86, 0.87],
            ),
            $this->profile(
                name: 'Aisyah Nur',
                registrationNo: 'NG-2026-0010',
                nik: '3201011805260010',
                sex: Sex::Perempuan,
                monthsOld: 6,
                facilityCode: 'PSY-MLT-03',
                birthWeight: 2.9,
                birthHeight: 48.0,
                weightZ: [-0.9, -1.0, -1.1, -1.2, -1.3, -1.4, -1.5],
                heightZ: [-0.5, -0.55, -0.6, -0.65, -0.7, -0.75, -0.8],
            ),
        ];
    }

    /**
     * @param  array<int, float>  $weightZ
     * @param  array<int, float>  $heightZ
     * @return array<string, mixed>
     */
    private function profile(
        string $name,
        string $registrationNo,
        string $nik,
        Sex $sex,
        int $monthsOld,
        string $facilityCode,
        float $birthWeight,
        float $birthHeight,
        array $weightZ,
        array $heightZ,
        bool $linkOrtu = false,
        ?Relationship $relationship = null,
        ?string $guardianEmail = null,
        array $notes = [],
        bool $recordedByNakes = false,
    ): array {
        $today = Carbon::today();
        $dateOfBirth = $today->copy()->subDays((int) round($monthsOld * 30.4375));
        $visits = [];

        foreach ($weightZ as $index => $z) {
            $ageDays = ($index + 1) * 30;

            if ($ageDays > $monthsOld * 30.4375) {
                break;
            }

            $visitDate = $dateOfBirth->copy()->addDays($ageDays);

            if ($visitDate->greaterThan($today)) {
                break;
            }

            $height = $this->growthEngine->valueForAgeZ('lhfa', $sex, $ageDays, $heightZ[$index] ?? 0.0);
            $weight = $this->growthEngine->valueForAgeZ('wfa', $sex, $ageDays, $z);

            if ($height === null || $weight === null) {
                continue;
            }

            $weight = round($weight, 2);
            $height = round($height + $this->jitter($index + 50, 0.02), 2);

            $visits[] = [
                'age_days' => $ageDays,
                'date' => $visitDate,
                'weight_kg' => $weight,
                'height_cm' => $height,
                'lila_cm' => round(max(9.0, $weight * 1.15 + 3.2), 1),
                'notes' => $notes[$index] ?? null,
            ];
        }

        return [
            'name' => $name,
            'registration_no' => $registrationNo,
            'nik' => $nik,
            'sex' => $sex,
            'date_of_birth' => $dateOfBirth,
            'facility_code' => $facilityCode,
            'birth_weight' => $birthWeight,
            'birth_height' => $birthHeight,
            'visits' => $visits,
            'link_ortu' => $linkOrtu,
            'relationship' => $relationship,
            'guardian_email' => $guardianEmail,
            'recorded_by_nakes' => $recordedByNakes,
        ];
    }

    private function jitter(int $seed, float $amplitude): float
    {
        return sin($seed * 1.7) * $amplitude;
    }

    private function seedFollowUps(Carbon $today): void
    {
        $kader = User::query()->where('email', 'kader@nutrigrow.id')->firstOrFail();
        $nakes = User::query()->where('email', 'nakes@nutrigrow.id')->firstOrFail();

        $plans = [
            'NG-2025-0003' => [
                ['MPASI padat gizi', 'Berikan menu protein hewani setiap hari dan catat makanan selama 2 minggu.', $today->copy()->addDays(14), 'Kader mendampingi ibu saat kunjungan rumah.'],
                ['Penimbangan ulang', 'Timbang ulang di Posyandu dua minggu lagi untuk melihat arah berat badan.', $today->copy()->addDays(14), null],
            ],
            'NG-2025-0005' => [
                ['Rujukan ke Puskesmas', 'Bawa anak ke Puskesmas Sukamaju untuk pemeriksaan oleh tenaga kesehatan.', $today->copy()->addDays(3), 'Prioritas karena berat badan tidak seimbang dengan tinggi badan.'],
            ],
            'NG-2024-0004' => [
                ['Pendampingan gizi', 'Lanjutkan menu seimbang dan pantau tinggi badan setiap bulan.', $today->copy()->addDays(30), null],
            ],
        ];

        foreach ($plans as $registrationNo => $items) {
            $child = Child::query()->where('registration_no', $registrationNo)->first();

            if (! $child) {
                continue;
            }

            foreach ($items as [$category, $plan, $targetDate, $notes]) {
                $child->followUps()->updateOrCreate(
                    ['category' => $category, 'action_plan' => $plan],
                    [
                        'created_by' => $category === 'Rujukan ke Puskesmas' ? $nakes->id : $kader->id,
                        'target_date' => $targetDate->toDateString(),
                        'status' => 'pending',
                        'notes' => $notes,
                    ],
                );
            }
        }
    }

    private function resolveOlderRisks(): void
    {
        $child = Child::query()->where('registration_no', 'NG-2024-0007')->first();

        if (! $child) {
            return;
        }

        $child->risks()
            ->whereIn('flag', ['stunting', 'stunting_severe'])
            ->where('status', 'open')
            ->update(['status' => 'resolved']);
    }
}
