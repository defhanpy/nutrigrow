<?php

namespace App\Enums;

enum Sex: string
{
    case LakiLaki = 'L';
    case Perempuan = 'P';

    public function label(): string
    {
        return match ($this) {
            self::LakiLaki => 'Laki-laki',
            self::Perempuan => 'Perempuan',
        };
    }

    public function whoCode(): int
    {
        return $this === self::LakiLaki ? 1 : 2;
    }
}
