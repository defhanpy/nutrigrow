<?php

namespace App\Support;

use App\Enums\Role;
use App\Models\User;

class PortalNavigation
{
    /**
     * @return array<int, array{label: string, icon: string, url: string, active: array<int, string>}>
     */
    public static function for(User $user): array
    {
        return match ($user->role) {
            Role::Kader => self::kader(),
            Role::OrangTua => self::orangTua(),
            Role::TenagaKesehatan => self::tenagaKesehatan(),
            Role::AdminPuskesmas => self::adminPuskesmas(),
        };
    }

    /**
     * @return array<int, array{label: string, icon: string, url: string, active: array<int, string>}>
     */
    private static function kader(): array
    {
        return [
            ['label' => 'Dashboard', 'icon' => 'ti-layout-dashboard', 'url' => route('kader.dashboard'), 'active' => ['kader.dashboard']],
            ['label' => 'Data Anak', 'icon' => 'ti-users', 'url' => route('children.index'), 'active' => ['children.index', 'children.show']],
            ['label' => 'Pengukuran', 'icon' => 'ti-scale', 'url' => route('kader.measurements'), 'active' => ['kader.measurements']],
            ['label' => 'Analisis Pertumbuhan', 'icon' => 'ti-chart-line', 'url' => route('kader.analytics'), 'active' => ['kader.analytics']],
            ['label' => 'Tindak Lanjut', 'icon' => 'ti-clipboard-check', 'url' => route('follow-ups.index'), 'active' => ['follow-ups.index']],
            ['label' => 'Edukasi Gizi', 'icon' => 'ti-salad', 'url' => route('nutrition.index'), 'active' => ['nutrition.index']],
            ['label' => 'Asisten Gizi AI', 'icon' => 'ti-sparkles', 'url' => route('nutrition.chat'), 'active' => ['nutrition.chat']],
        ];
    }

    /**
     * @return array<int, array{label: string, icon: string, url: string, active: array<int, string>}>
     */
    private static function orangTua(): array
    {
        return [
            ['label' => 'Beranda', 'icon' => 'ti-home', 'url' => route('ortu.dashboard'), 'active' => ['ortu.dashboard']],
            ['label' => 'Pertumbuhan Anak', 'icon' => 'ti-chart-line', 'url' => route('ortu.growth'), 'active' => ['ortu.growth']],
            ['label' => 'Tanya Asisten AI', 'icon' => 'ti-sparkles', 'url' => route('nutrition.chat'), 'active' => ['nutrition.chat']],
            ['label' => 'Edukasi Gizi', 'icon' => 'ti-salad', 'url' => route('nutrition.index'), 'active' => ['nutrition.index']],
            ['label' => 'Jadwal', 'icon' => 'ti-calendar-event', 'url' => route('ortu.schedule'), 'active' => ['ortu.schedule']],
            ['label' => 'Profil', 'icon' => 'ti-user-circle', 'url' => route('profile.show'), 'active' => ['profile.show']],
        ];
    }

    /**
     * @return array<int, array{label: string, icon: string, url: string, active: array<int, string>}>
     */
    private static function tenagaKesehatan(): array
    {
        return [
            ['label' => 'Dashboard Review', 'icon' => 'ti-layout-dashboard', 'url' => route('nakes.dashboard'), 'active' => ['nakes.dashboard']],
            ['label' => 'Daftar Peninjauan', 'icon' => 'ti-alert-triangle', 'url' => route('nakes.reviews'), 'active' => ['nakes.reviews']],
            ['label' => 'Profil Anak', 'icon' => 'ti-users', 'url' => route('children.index'), 'active' => ['children.index', 'children.show']],
            ['label' => 'Hasil Analisis', 'icon' => 'ti-activity', 'url' => route('nakes.analytics'), 'active' => ['nakes.analytics']],
            ['label' => 'Tindak Lanjut', 'icon' => 'ti-clipboard-check', 'url' => route('follow-ups.index'), 'active' => ['follow-ups.index']],
        ];
    }

    /**
     * @return array<int, array{label: string, icon: string, url: string, active: array<int, string>}>
     */
    private static function adminPuskesmas(): array
    {
        return [
            ['label' => 'Dashboard Wilayah', 'icon' => 'ti-map-2', 'url' => route('puskesmas.dashboard'), 'active' => ['puskesmas.dashboard']],
            ['label' => 'Posyandu', 'icon' => 'ti-building-community', 'url' => route('puskesmas.posyandu'), 'active' => ['puskesmas.posyandu']],
            ['label' => 'Pengguna', 'icon' => 'ti-user-cog', 'url' => route('puskesmas.users'), 'active' => ['puskesmas.users']],
            ['label' => 'Laporan', 'icon' => 'ti-file-download', 'url' => route('puskesmas.reports'), 'active' => ['puskesmas.reports']],
            ['label' => 'Pengaturan', 'icon' => 'ti-settings', 'url' => route('puskesmas.settings'), 'active' => ['puskesmas.settings']],
        ];
    }
}
