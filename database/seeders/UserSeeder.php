<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\HealthFacility;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $puskesmas = HealthFacility::query()->where('code', 'PKM-SKM-01')->firstOrFail();
        $melati01 = HealthFacility::query()->where('code', 'PSY-MLT-01')->firstOrFail();

        $accounts = [
            [
                'name' => 'Kader Melati 01',
                'email' => 'kader@nutrigrow.id',
                'role' => Role::Kader,
                'facility_id' => $melati01->id,
            ],
            [
                'name' => 'Wali Balita Afiqah',
                'email' => 'ortu@nutrigrow.id',
                'role' => Role::OrangTua,
                'facility_id' => $melati01->id,
            ],
            [
                'name' => 'dr. Rian Pratama',
                'email' => 'nakes@nutrigrow.id',
                'role' => Role::TenagaKesehatan,
                'facility_id' => $puskesmas->id,
            ],
            [
                'name' => 'Petugas Gizi Puskesmas',
                'email' => 'admin@nutrigrow.id',
                'role' => Role::AdminPuskesmas,
                'facility_id' => $puskesmas->id,
            ],
        ];

        foreach ($accounts as $account) {
            User::query()->updateOrCreate(
                ['email' => $account['email']],
                [
                    ...$account,
                    'password' => 'rahasia123',
                    'status' => UserStatus::Active,
                    'email_verified_at' => now(),
                ],
            );
        }
    }
}
