<?php

namespace App\Enums;

enum RiskSeverity: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Perlu dipantau',
            self::Medium => 'Perlu tindak lanjut',
            self::High => 'Prioritas peninjauan',
        };
    }
}
