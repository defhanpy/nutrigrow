<?php

namespace App\Enums;

enum FacilityType: string
{
    case Puskesmas = 'puskesmas';
    case Posyandu = 'posyandu';

    public function label(): string
    {
        return match ($this) {
            self::Puskesmas => 'Puskesmas',
            self::Posyandu => 'Posyandu',
        };
    }
}
