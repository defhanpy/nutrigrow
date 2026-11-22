<?php

namespace Database\Seeders;

use App\Models\NutritionKnowledge;
use Illuminate\Database\Seeder;

class NutritionKnowledgeSeeder extends Seeder
{
    public function run(): void
    {
        $entries = [
            [
                'age_group' => '0-5',
                'title' => 'ASI eksklusif sampai 6 bulan',
                'content' => 'Bayi hanya membutuhkan ASI sampai usia 6 bulan. ASI mengandung zat gizi dan antibodi yang melindungi bayi dari infeksi. Berikan ASI sesuai kebutuhan, termasuk malam hari, dan tidak perlu menambahkan air atau makanan lain tanpa arahan tenaga kesehatan.',
                'local_food_examples' => ['ASI'],
                'key_nutrients' => ['Protein', 'Lemak', 'Vitamin A', 'Antibodi'],
                'source' => 'Kementerian Kesehatan RI — Pedoman Gizi Seimbang',
            ],
            [
                'age_group' => '6-8',
                'title' => 'Mengenal MPASI pertama',
                'content' => 'MPASI dimulai saat bayi berusia 6 bulan dengan tekstur lumat. Berikan 2–3 sendok makan, lalu tingkatkan bertahap. Kenalkan satu bahan baru setiap 2–3 hari agar mudah mengenali alergi. Tetap berikan ASI.',
                'local_food_examples' => ['Bubur beras + daun kelor', 'Puree alpukat', 'Telur rebus lumat', 'Ubi jalar kukus'],
                'key_nutrients' => ['Zat besi', 'Protein', 'Vitamin A'],
                'source' => 'Kementerian Kesehatan RI — Resep MPASI Lokal',
            ],
            [
                'age_group' => '9-11',
                'title' => 'Protein hewani setiap hari',
                'content' => 'Pada usia 9–11 bulan, anak mulai belajar makan makanan cincang. Sertakan protein hewani setiap hari seperti telur, ikan, atau hati ayam. Protein hewani membantu pertumbuhan dan mencegah anemia.',
                'local_food_examples' => ['Nasi tim + ikan kembung', 'Bubur tempe', 'Hati ayam lumat', 'Sayur bayam cincang'],
                'key_nutrients' => ['Protein hewani', 'Zat besi', 'Seng', 'Omega-3'],
                'source' => 'Kementerian Kesehatan RI — Isi Piringku Balita',
            ],
            [
                'age_group' => '12-23',
                'title' => 'Makan bersama keluarga',
                'content' => 'Anak usia 12–23 bulan dapat makan menu keluarga dengan tekstur yang disesuaikan. Berikan 3 kali makan utama dan 1–2 kali selingan bergizi. Batasi makanan manis kemasan dan biasakan minum air putih.',
                'local_food_examples' => ['Pepes lele + sayur', 'Tempe orek + nasi', 'Sup ayam + ubi', 'Buah potong'],
                'key_nutrients' => ['Karbohidrat', 'Protein', 'Serat', 'Vitamin'],
                'source' => 'Kementerian Kesehatan RI — Pedoman Gizi Seimbang',
            ],
            [
                'age_group' => '24-59',
                'title' => 'Menu seimbang untuk balita aktif',
                'content' => 'Balita aktif membutuhkan variasi menu dari lima kelompok pangan: makanan pokok, lauk pauk, sayur, buah, dan air putih. Ajak anak memilih sayur dan buah favoritnya agar lebih semangat makan.',
                'local_food_examples' => ['Nasi + ikan kembung + bayam', 'Bubur kacang hijau', 'Tempe bacem + sayur bening', 'Alpukat potong'],
                'key_nutrients' => ['Protein', 'Zat besi', 'Kalsium', 'Serat'],
                'source' => 'Kementerian Kesehatan RI — Isi Piringku Balita',
            ],
        ];

        foreach ($entries as $entry) {
            NutritionKnowledge::query()->updateOrCreate(
                ['age_group' => $entry['age_group'], 'title' => $entry['title']],
                $entry,
            );
        }
    }
}
