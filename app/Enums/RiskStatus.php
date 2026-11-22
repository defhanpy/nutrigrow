<?php

namespace App\Enums;

enum RiskStatus: string
{
    case Open = 'open';
    case Resolved = 'resolved';
    case Dismissed = 'dismissed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Terbuka',
            self::Resolved => 'Selesai',
            self::Dismissed => 'Ditolak',
        };
    }
}
