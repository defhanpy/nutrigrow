<?php

namespace App\Enums;

enum MeasurementMethod: string
{
    case Terlentang = 'terlentang';
    case Berdiri = 'berdiri';

    public function label(): string
    {
        return match ($this) {
            self::Terlentang => 'Terlentang (berbaring)',
            self::Berdiri => 'Berdiri',
        };
    }

    public function isRecumbent(): bool
    {
        return $this === self::Terlentang;
    }
}
