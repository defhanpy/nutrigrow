<?php

namespace App\Services;

use App\Enums\MeasurementMethod;
use App\Enums\Sex;
use App\Models\Child;
use Illuminate\Support\Carbon;
use RuntimeException;

class WhoGrowthEngineService
{
    private const MAX_AGE_DAYS = 1826;

    private const MIN_AGE_DAYS = 0;

    /** @var array<string, array<string, array<int, array{0: float, 1: float, 2: float, 3: float}>>>|null */
    private static ?array $standards = null;

    /**
     * Calculate WHO 2006 z-scores and Permenkes classifications for one measurement.
     *
     * @return array{
     *     age_in_days: int,
     *     corrected_height_cm: float,
     *     bmi: float|null,
     *     wfa_z: float|null,
     *     lhfa_z: float|null,
     *     wfh_z: float|null,
     *     bfa_z: float|null,
     *     wfa_status: string|null,
     *     lhfa_status: string|null,
     *     wfh_status: string|null,
     *     bfa_status: string|null,
     *     overall_status: string
     * }
     */
    public function calculateZScores(
        Child $child,
        float $weightKg,
        float $heightCm,
        string $method,
        Carbon $measuredAt,
    ): array {
        $ageInDays = $this->ageInDays($child, $measuredAt);
        $sex = $child->sex instanceof Sex ? $child->sex : Sex::from($child->sex);
        $correctedHeight = $this->correctHeight($heightCm, $ageInDays, $method);

        $wfaZ = $this->zForAge('wfa', $sex, $ageInDays, $weightKg);
        $lhfaZ = $this->zForAge('lhfa', $sex, $ageInDays, $correctedHeight);
        $wfhZ = $this->zForHeight($sex, $ageInDays, $correctedHeight, $weightKg);
        $bmi = $correctedHeight > 0 ? $weightKg / (($correctedHeight / 100) ** 2) : null;
        $bfaZ = $bmi !== null ? $this->zForAge('bfa', $sex, $ageInDays, $bmi) : null;

        $wfaStatus = $this->classifyWeightForAge($wfaZ);
        $lhfaStatus = $this->classifyLengthForAge($lhfaZ);
        $wfhStatus = $this->classifyWeightForHeight($wfhZ);
        $bfaStatus = $this->classifyBmiForAge($bfaZ);

        return [
            'age_in_days' => $ageInDays,
            'corrected_height_cm' => round($correctedHeight, 2),
            'bmi' => $bmi !== null ? round($bmi, 2) : null,
            'wfa_z' => $this->roundZ($wfaZ),
            'lhfa_z' => $this->roundZ($lhfaZ),
            'wfh_z' => $this->roundZ($wfhZ),
            'bfa_z' => $this->roundZ($bfaZ),
            'wfa_status' => $wfaStatus,
            'lhfa_status' => $lhfaStatus,
            'wfh_status' => $wfhStatus,
            'bfa_status' => $bfaStatus,
            'overall_status' => $this->overallStatus(
                $wfaStatus,
                $lhfaStatus,
                $wfhStatus,
                $bfaStatus,
            ),
        ];
    }

    /**
     * Explain sequential growth patterns in plain Indonesian without diagnosing.
     *
     * @return array{
     *     findings: array<int, array{code: string, title: string, description: string, severity: string, evidence: array<string, mixed>, occurrences?: int}>,
     *     visits_analyzed: int,
     *     summary: string
     * }
     */
    public function evaluateTrajectory(Child $child): array
    {
        $measurements = $child->measurements()
            ->with('assessment')
            ->orderBy('measured_at')
            ->orderBy('id')
            ->get();

        $findings = [];

        if ($measurements->count() < 2) {
            return [
                'findings' => [],
                'visits_analyzed' => $measurements->count(),
                'summary' => 'Riwayat pengukuran masih terbatas. Tambahkan kunjungan berikutnya agar pola pertumbuhan dapat dibaca.',
            ];
        }

        $previous = null;
        $seenDates = [];
        $previousAgeDays = null;
        $previousWfaZ = null;

        foreach ($measurements as $measurement) {
            $dateKey = $measurement->measured_at->toDateString();
            $ageDays = $this->ageInDays($child, $measurement->measured_at);
            $sex = $child->sex instanceof Sex ? $child->sex : Sex::from($child->sex);
            $height = $this->correctHeight(
                (float) $measurement->height_cm,
                $ageDays,
                $measurement->method instanceof MeasurementMethod
                    ? $measurement->method->value
                    : (string) $measurement->method,
            );
            $wfaZ = $this->zForAge('wfa', $sex, $ageDays, (float) $measurement->weight_kg);
            $lhfaZ = $this->zForAge('lhfa', $sex, $ageDays, $height);

            if (isset($seenDates[$dateKey])) {
                $findings[] = $this->finding(
                    'duplicate_measurement_date',
                    'Ada dua catatan pada tanggal yang sama',
                    'Terdapat lebih dari satu pengukuran pada tanggal '.$measurement->measured_at->translatedFormat('d F Y').'. Mohon periksa kembali catatan yang benar sebelum menarik kesimpulan.',
                    'low',
                    ['tanggal' => $dateKey],
                );
            }
            $seenDates[$dateKey] = true;

            if ($previous !== null) {
                $gapDays = max(1, (int) $previous->measured_at->diffInDays($measurement->measured_at));
                $weightGain = (float) $measurement->weight_kg - (float) $previous->weight_kg;
                $minimumGain = $this->minimumMonthlyGain($previousAgeDays ?? $ageDays) * ($gapDays / 30.4375);

                if ($gapDays >= 21 && $weightGain < $minimumGain && $weightGain < 0.05) {
                    $findings[] = $this->finding(
                        'growth_faltering',
                        'Kenaikan berat badan melambat',
                        'Pada kunjungan '.$measurement->measured_at->translatedFormat('d F Y').', kenaikan berat badan tercatat '.$this->formatNumber($weightGain, 2).' kg dalam '.$gapDays.' hari. Angka ini berada di bawah panduan kenaikan minimum untuk usia tersebut. Perlu ditanyakan pola makan, riwayat sakit, dan jadwal kunjungan berikutnya.',
                        'medium',
                        [
                            'tanggal' => $dateKey,
                            'kenaikan_kg' => round($weightGain, 2),
                            'ambang_minimum_kg' => round($minimumGain, 2),
                            'jarak_hari' => $gapDays,
                        ],
                    );
                }

                if ($weightGain < -0.2) {
                    $findings[] = $this->finding(
                        'weight_loss',
                        'Berat badan menurun',
                        'Berat badan tercatat turun '.$this->formatNumber(abs($weightGain), 2).' kg dibanding kunjungan sebelumnya. Periksa apakah anak sedang sakit atau ada kesalahan pencatatan, lalu ukur ulang bila perlu.',
                        'medium',
                        ['tanggal' => $dateKey, 'penurunan_kg' => round(abs($weightGain), 2)],
                    );
                }

                $heightLoss = (float) $previous->height_cm - (float) $measurement->height_cm;

                if ($heightLoss > 0.5) {
                    $findings[] = $this->finding(
                        'height_shrinkage',
                        'Tinggi badan lebih rendah dari catatan sebelumnya',
                        'Tinggi badan pada '.$measurement->measured_at->translatedFormat('d F Y').' tercatat '.$this->formatNumber($heightLoss, 1).' cm lebih rendah. Biasanya ini terjadi karena perbedaan posisi alat ukur. Mohon ukur ulang dengan posisi yang sama.',
                        'low',
                        ['tanggal' => $dateKey, 'selisih_cm' => round($heightLoss, 1)],
                    );
                }

                if ($wfaZ !== null && $previousWfaZ !== null) {
                    $drop = $previousWfaZ - $wfaZ;

                    if ($drop >= 0.67) {
                        $findings[] = $this->finding(
                            'weight_percentile_drop',
                            'Posisi berat badan menurun',
                            'Posisi berat badan anak pada grafik turun cukup jelas dibanding kunjungan sebelumnya. Ini bukan diagnosis, tetapi tanda yang perlu ditinjau bersama tenaga kesehatan.',
                            'medium',
                            ['tanggal' => $dateKey, 'penurunan_sd' => round($drop, 2)],
                        );
                    }
                }

                if ($gapDays > 60) {
                    $findings[] = $this->finding(
                        'missed_appointment',
                        'Jarak kunjungan terlalu panjang',
                        'Ada jeda '.$gapDays.' hari antara dua pengukuran. Pemantauan pertumbuhan sebaiknya dilakukan setiap bulan agar perubahan lebih mudah dikenali.',
                        'low',
                        ['tanggal' => $dateKey, 'jarak_hari' => $gapDays],
                    );
                }
            }

            $previous = $measurement;
            $previousAgeDays = $ageDays;
            $previousWfaZ = $wfaZ;
        }

        $latest = $measurements->last();
        $daysSinceLatest = (int) $latest->measured_at->diffInDays(Carbon::today());

        if ($daysSinceLatest > 60) {
            $findings[] = $this->finding(
                'overdue_visit',
                'Sudah lama tidak ditimbang',
                'Pengukuran terakhir dilakukan '.$daysSinceLatest.' hari yang lalu. Ajak keluarga untuk datang ke Posyandu agar pertumbuhan tetap terpantau.',
                'low',
                ['hari_sejak_pengukuran' => $daysSinceLatest],
            );
        }

        $findings = $this->collapseRepeatedFindings($findings);

        return [
            'findings' => $findings,
            'visits_analyzed' => $measurements->count(),
            'summary' => $findings === []
                ? 'Pola pertumbuhan dari '.$measurements->count().' kunjungan tidak menunjukkan hal yang perlu ditinjau khusus.'
                : 'Ada '.count($findings).' hal yang perlu ditinjau dari riwayat kunjungan. Temuan ini adalah bahan diskusi, bukan diagnosis.',
        ];
    }

    /**
     * Inverse LMS helper for age-based indicators, used to build realistic demo data.
     */
    public function valueForAgeZ(string $indicator, Sex $sex, int $ageInDays, float $targetZ): ?float
    {
        $lms = $this->lmsForAge($indicator, $sex, $ageInDays);

        if ($lms === null) {
            return null;
        }

        return $this->inverseZ($targetZ, ...$lms);
    }

    /**
     * @return array{0: float, 1: float, 2: float}|null
     */
    public function lmsForAge(string $indicator, Sex $sex, int $ageInDays): ?array
    {
        $data = $this->standards()[$indicator][$sex->value] ?? null;

        if ($data === null) {
            return null;
        }

        $ageInDays = max(self::MIN_AGE_DAYS, min(self::MAX_AGE_DAYS, $ageInDays));
        $index = min(count($data) - 1, max(0, $ageInDays));

        if (! isset($data[$index])) {
            return null;
        }

        [, $l, $m, $s] = $data[$index];

        return [$l, $m, $s];
    }

    /**
     * @return array{0: float, 1: float, 2: float}|null
     */
    public function lmsForHeight(string $indicator, Sex $sex, float $heightCm): ?array
    {
        $data = $this->standards()[$indicator][$sex->value] ?? null;

        if ($data === null || $heightCm <= 0) {
            return null;
        }

        $low = floor($heightCm * 10) / 10;
        $high = round($low + 0.1, 1);
        $lower = $this->lookupHeight($data, $low);
        $upper = $this->lookupHeight($data, $high);

        if ($lower === null) {
            return null;
        }

        if ($upper === null) {
            return $lower;
        }

        $ratio = max(0.0, min(1.0, ($heightCm - $low) / 0.1));

        return [
            $lower[0] + $ratio * ($upper[0] - $lower[0]),
            $lower[1] + $ratio * ($upper[1] - $lower[1]),
            $lower[2] + $ratio * ($upper[2] - $lower[2]),
        ];
    }

    public function classifyWeightForAge(?float $z): ?string
    {
        return match (true) {
            $z === null => null,
            $z < -3 => 'Gizi Buruk',
            $z < -2 => 'Gizi Kurang',
            $z <= 1 => 'Gizi Baik',
            $z <= 2 => 'Berisiko Gizi Lebih',
            default => 'Gizi Lebih',
        };
    }

    public function classifyLengthForAge(?float $z): ?string
    {
        return match (true) {
            $z === null => null,
            $z < -3 => 'Sangat Pendek',
            $z < -2 => 'Pendek',
            $z <= 3 => 'Normal',
            default => 'Tinggi',
        };
    }

    public function classifyWeightForHeight(?float $z): ?string
    {
        return match (true) {
            $z === null => null,
            $z < -3 => 'Gizi Buruk (Wasting Berat)',
            $z < -2 => 'Gizi Kurang (Wasting)',
            $z <= 2 => 'Normal',
            $z <= 3 => 'Berisiko Gizi Lebih',
            default => 'Gizi Lebih',
        };
    }

    public function classifyBmiForAge(?float $z): ?string
    {
        return match (true) {
            $z === null => null,
            $z < -3 => 'Gizi Buruk (Wasting Berat)',
            $z < -2 => 'Gizi Kurang (Wasting)',
            $z <= 1 => 'Normal',
            $z <= 2 => 'Berisiko Gizi Lebih',
            default => 'Gizi Lebih',
        };
    }

    public function correctHeight(float $heightCm, int $ageInDays, string $method): float
    {
        if ($ageInDays < 731 && $method === MeasurementMethod::Berdiri->value) {
            return round($heightCm + 0.7, 2);
        }

        if ($ageInDays >= 731 && $method === MeasurementMethod::Terlentang->value) {
            return round($heightCm - 0.7, 2);
        }

        return $heightCm;
    }

    private function zForAge(string $indicator, Sex $sex, int $ageInDays, float $value): ?float
    {
        if ($value <= 0 || $ageInDays < self::MIN_AGE_DAYS || $ageInDays > self::MAX_AGE_DAYS) {
            return null;
        }

        $lms = $this->lmsForAge($indicator, $sex, $ageInDays);

        return $lms === null ? null : $this->zScore($value, ...$lms);
    }

    private function zForHeight(Sex $sex, int $ageInDays, float $heightCm, float $weightKg): ?float
    {
        if ($weightKg <= 0) {
            return null;
        }

        $indicator = $ageInDays < 731 ? 'wfl' : 'wfh';
        $lms = $this->lmsForHeight($indicator, $sex, $heightCm);

        return $lms === null ? null : $this->zScore($weightKg, ...$lms);
    }

    private function zScore(float $value, float $l, float $m, float $s): float
    {
        if ($m <= 0 || $s <= 0) {
            throw new RuntimeException('Data rujukan pertumbuhan tidak valid.');
        }

        $z = abs($l) < 1e-9
            ? log($value / $m) / $s
            : (((($value / $m) ** $l) - 1) / ($l * $s));

        if (abs($l) < 1e-9) {
            return $z;
        }

        $sd3Pos = $m * ((1 + $l * $s * 3) ** (1 / $l));
        $sd2Pos = $m * ((1 + $l * $s * 2) ** (1 / $l));
        $sd3Neg = $m * ((1 + $l * $s * -3) ** (1 / $l));
        $sd2Neg = $m * ((1 + $l * $s * -2) ** (1 / $l));

        if ($z > 3) {
            $spread = $sd3Pos - $sd2Pos;
            $z = $spread > 0 ? 3 + (($value - $sd3Pos) / $spread) : 3;
        } elseif ($z < -3) {
            $spread = $sd2Neg - $sd3Neg;
            $z = $spread > 0 ? -3 + (($value - $sd3Neg) / $spread) : -3;
        }

        return $z;
    }

    /**
     * @param  array<int, array{0: float, 1: float, 2: float, 3: float}>  $data
     * @return array{0: float, 1: float, 2: float}|null
     */
    private function lookupHeight(array $data, float $heightCm): ?array
    {
        $low = 0;
        $high = count($data) - 1;

        while ($low <= $high) {
            $middle = intdiv($low + $high, 2);
            $current = $data[$middle][0];

            if (abs($current - $heightCm) < 1e-9) {
                return [$data[$middle][1], $data[$middle][2], $data[$middle][3]];
            }

            if ($current < $heightCm) {
                $low = $middle + 1;
            } else {
                $high = $middle - 1;
            }
        }

        return null;
    }

    private function inverseZ(float $z, float $l, float $m, float $s): float
    {
        if (abs($l) < 1e-9) {
            return round($m * exp($s * $z), 2);
        }

        return round($m * ((1 + $l * $s * $z) ** (1 / $l)), 2);
    }

    private function overallStatus(
        ?string $wfaStatus,
        ?string $lhfaStatus,
        ?string $wfhStatus,
        ?string $bfaStatus,
    ): string {
        $candidates = [
            $wfaStatus,
            $lhfaStatus,
            $wfhStatus,
            $bfaStatus,
        ];

        foreach (['Gizi Buruk (Wasting Berat)', 'Gizi Buruk', 'Sangat Pendek', 'Gizi Kurang (Wasting)', 'Gizi Kurang', 'Pendek'] as $priority) {
            if (in_array($priority, $candidates, true)) {
                return $priority;
            }
        }

        foreach (['Gizi Lebih', 'Berisiko Gizi Lebih'] as $priority) {
            if (in_array($priority, $candidates, true)) {
                return $priority;
            }
        }

        return 'Gizi Baik';
    }

    private function ageInDays(Child $child, Carbon $measuredAt): int
    {
        return max(
            0,
            (int) $child->date_of_birth->copy()->startOfDay()
                ->diffInDays($measuredAt->copy()->startOfDay()),
        );
    }

    private function minimumMonthlyGain(int $ageInDays): float
    {
        return match (true) {
            $ageInDays < 91 => 0.50,
            $ageInDays < 183 => 0.35,
            $ageInDays < 274 => 0.22,
            $ageInDays < 365 => 0.18,
            $ageInDays < 730 => 0.12,
            default => 0.08,
        };
    }

    private function roundZ(?float $z): ?float
    {
        return $z === null ? null : round($z, 2);
    }

    /**
     * Keep one readable finding per pattern, retaining the most recent evidence.
     *
     * @param  array<int, array{code: string, title: string, description: string, severity: string, evidence: array<string, mixed>}>  $findings
     * @return array<int, array{code: string, title: string, description: string, severity: string, evidence: array<string, mixed>, occurrences: int}>
     */
    private function collapseRepeatedFindings(array $findings): array
    {
        $collapsed = [];

        foreach ($findings as $finding) {
            $occurrences = ($collapsed[$finding['code']]['occurrences'] ?? 0) + 1;
            $collapsed[$finding['code']] = [...$finding, 'occurrences' => $occurrences];
        }

        return array_values(array_map(function (array $finding): array {
            if ($finding['occurrences'] > 1) {
                $finding['description'] .= ' Hal serupa tercatat '.$finding['occurrences'].' kali dalam riwayat kunjungan.';
            }

            return $finding;
        }, $collapsed));
    }

    /**
     * @return array{code: string, title: string, description: string, severity: string, evidence: array<string, mixed>}
     */
    private function finding(
        string $code,
        string $title,
        string $description,
        string $severity,
        array $evidence,
    ): array {
        return [
            'code' => $code,
            'title' => $title,
            'description' => $description,
            'severity' => $severity,
            'evidence' => $evidence,
        ];
    }

    private function formatNumber(float $value, int $decimals): string
    {
        return number_format($value, $decimals, ',', '.');
    }

    /**
     * @return array<string, array<string, array<int, array{0: float, 1: float, 2: float, 3: float}>>>
     */
    private function standards(): array
    {
        if (self::$standards !== null) {
            return self::$standards;
        }

        $path = storage_path('app/who/who-lms.json');

        if (! is_file($path)) {
            throw new RuntimeException('Data rujukan pertumbuhan belum tersedia.');
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Data rujukan pertumbuhan tidak dapat dibaca.');
        }

        unset($decoded['meta']);

        self::$standards = $decoded;

        return self::$standards;
    }
}
