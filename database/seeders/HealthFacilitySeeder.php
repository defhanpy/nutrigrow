<?php

namespace Database\Seeders;

use App\Enums\FacilityType;
use App\Models\HealthFacility;
use Illuminate\Database\Seeder;

class HealthFacilitySeeder extends Seeder
{
    public function run(): void
    {
        $puskesmas = HealthFacility::query()->updateOrCreate(
            ['code' => 'PKM-SKM-01'],
            [
                'name' => 'Puskesmas Sukamaju',
                'type' => FacilityType::Puskesmas,
                'address' => 'Jl. Raya Sukamaju No. 12, Kecamatan Sukamaju',
                'parent_id' => null,
            ],
        );

        $posyandu = [
            ['code' => 'PSY-MLT-01', 'name' => 'Posyandu Melati 01', 'address' => 'Balai RW 01, Desa Sukamaju'],
            ['code' => 'PSY-MLT-02', 'name' => 'Posyandu Melati 02', 'address' => 'Balai RW 02, Desa Sukamaju'],
            ['code' => 'PSY-MLT-03', 'name' => 'Posyandu Melati 03', 'address' => 'Balai RW 03, Desa Sukamaju'],
        ];

        foreach ($posyandu as $facility) {
            HealthFacility::query()->updateOrCreate(
                ['code' => $facility['code']],
                [
                    'name' => $facility['name'],
                    'type' => FacilityType::Posyandu,
                    'address' => $facility['address'],
                    'parent_id' => $puskesmas->id,
                ],
            );
        }
    }
}
