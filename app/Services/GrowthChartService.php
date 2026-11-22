<?php

namespace App\Services;

use App\Enums\Sex;
use App\Models\Child;
use App\Models\Measurement;

class GrowthChartService
{
    public function __construct(private readonly WhoGrowthEngineService $engine) {}

    /**
     * Build a KMS-style weight-for-age chart with national reference bands.
     *
     * @return array<string, mixed>
     */
    public function weightChart(Child $child): array
    {
        $sex = $child->sex instanceof Sex ? $child->sex : Sex::from($child->sex);
        $ageMonths = max(6, $child->ageInMonths() + 3);
        $maxMonth = min(60, max(12, $ageMonths));

        $labels = [];
        $median = [];
        $lower = [];
        $upper = [];
        $lowerThree = [];
        $upperThree = [];

        for ($month = 0; $month <= $maxMonth; $month++) {
            $days = $month * 30;
            $labels[] = $month;
            $median[] = $this->engine->valueForAgeZ('wfa', $sex, $days, 0);
            $lower[] = $this->engine->valueForAgeZ('wfa', $sex, $days, -2);
            $upper[] = $this->engine->valueForAgeZ('wfa', $sex, $days, 2);
            $lowerThree[] = $this->engine->valueForAgeZ('wfa', $sex, $days, -3);
            $upperThree[] = $this->engine->valueForAgeZ('wfa', $sex, $days, 3);
        }

        $childPoints = $child->measurements()
            ->orderBy('measured_at')
            ->get()
            ->map(fn (Measurement $measurement): array => [
                'x' => round(max(0, $child->ageInDays($measurement->measured_at)) / 30.4375, 2),
                'y' => (float) $measurement->weight_kg,
                'label' => $measurement->measured_at->translatedFormat('d M Y'),
            ])
            ->values()
            ->all();

        return [
            'labels' => $labels,
            'median' => $median,
            'lower' => $lower,
            'upper' => $upper,
            'lowerThree' => $lowerThree,
            'upperThree' => $upperThree,
            'points' => $childPoints,
            'child_name' => $child->full_name,
            'age_label' => $child->ageLabel(),
            'y_label' => 'Berat badan (kg)',
            'x_label' => 'Usia (bulan)',
        ];
    }
}
