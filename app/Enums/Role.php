<?php

namespace App\Enums;

enum Role: string
{
    case Kader = 'kader';
    case OrangTua = 'ortu';
    case TenagaKesehatan = 'nakes';
    case AdminPuskesmas = 'admin_puskesmas';

    public function label(): string
    {
        return match ($this) {
            self::Kader => 'Kader Posyandu',
            self::OrangTua => 'Orang Tua / Wali',
            self::TenagaKesehatan => 'Tenaga Kesehatan',
            self::AdminPuskesmas => 'Administrator Puskesmas',
        };
    }

    public function dashboardRoute(): string
    {
        return match ($this) {
            self::Kader => 'kader.dashboard',
            self::OrangTua => 'ortu.dashboard',
            self::TenagaKesehatan => 'nakes.dashboard',
            self::AdminPuskesmas => 'puskesmas.dashboard',
        };
    }
}
