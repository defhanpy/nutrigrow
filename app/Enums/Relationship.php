<?php

namespace App\Enums;

enum Relationship: string
{
    case Ibu = 'ibu';
    case Ayah = 'ayah';
    case Wali = 'wali';

    public function label(): string
    {
        return match ($this) {
            self::Ibu => 'Ibu',
            self::Ayah => 'Ayah',
            self::Wali => 'Wali',
        };
    }
}
