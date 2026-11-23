# NUTRIGROW — AI-Powered Child Growth & Nutrition Intelligence

Platform pemantauan tumbuh kembang anak berbasis standar WHO, analisis pola pertumbuhan longitudinal (*Growth Intelligence Loop*), edukasi gizi pangan lokal, dan kolaborasi 4 pilar layanan: Kader Posyandu, Orang Tua, Tenaga Kesehatan (Bidan/Dokter), serta Administrator Puskesmas.

Karya inovasi teknologi kesehatan diajukan untuk **IT RUSH 2026** (Kategori Web Development — Subtema: Kesehatan Masyarakat) diselenggarakan oleh HIMATIF Universitas Bengkulu.

---

## 🌟 Fitur Utama & Inovasi

1. **Growth Intelligence Loop (Standar Antropometri WHO)**:
   - Perhitungan eksak indikator antropometri berbasis standar baku WHO Child Growth Standards (0–60 bulan):
     - *Weight-for-Age* (BB/U — Status Berat Badan)
     - *Length/Height-for-Age* (TB/U — Indikator Stunting)
     - *Weight-for-Length/Height* (BB/TB — Indikator Wasting)
     - *BMI-for-Age* (IMT/U — Indikator Massa Tubuh)
2. **Longitudinal Trajectory & Explainable Health Alerts**:
   - Analisis tren pertumbuhan antar-kunjungan: mendeteksi perlambatan kenaikan berat badan (*growth faltering*), penurunan garis persentil, atau anomali pengukuran tanpa vonis medis otomatis.
   - Penjelasan berbasis bukti dengan rekomendasi langkah verifikasi lapangan.
3. **Personalized Nutrition Intelligence (Pangan Lokal)**:
   - Rekomendasi menu harian dan MPASI berbasis kearifan pangan lokal terjangkau (tempe, lele, telur, daun kelor, hati ayam, ikan kembung) terpetakan sesuai kelompok usia anak.
4. **Multi-Role Collaborative Ecosystem**:
   - **Kader Posyandu**: Pencatatan cepat antropometri di meja penimbangan, kurva KMS digital (ApexCharts), dan pencatatan tindak lanjut.
   - **Orang Tua**: Ringkasan pertumbuhan dalam bahasa ramah dan panduan menu gizi keluarga.
   - **Tenaga Kesehatan**: Antrean verifikasi balita dengan penanda pola risiko, telaah riwayat longitudinal, dan catatan arahan medis.
   - **Puskesmas**: Dashboard agregat cakupan wilayah (D/S), prevalensi status gizi, dan perbandingan performa per Posyandu.

---

## 🛠️ Tech Stack & Atribusi Aset Terbuka

Sesuai aturan resmi Buku Panduan IT RUSH 2026 (Poin V.5 mengenai transparansi penggunaan framework, pustaka, dan aset open-source):
- **Backend & Web Framework**: Laravel 13 (PHP 8.4) — Arsitektur Clean Code, Service Layer, dan DRY Blade Templating.
- **Database Engine**: PostgreSQL 17 (Relational Database dengan integritas referensial dan audit trail).
- **UI Architecture & Theme Starter**: [Boron Bootstrap 5 Neubrutalism Admin Dashboard](https://coderthemes.com).
- **Iconography**: Tabler Icons & Solar Icons.
- **Charts & Visualization**: ApexCharts (Kurva persentil KMS WHO Median, ±2 SD, ±3 SD).
- **Testing**: PHPUnit / Pest framework untuk validasi regresi antropometri WHO.

---

## 🚀 Panduan Menjalankan Aplikasi

### Persyaratan Sistem
- PHP >= 8.2 (dengan ekstensi `pdo_pgsql`, `mbstring`, `bcmath`, `xml`)
- Composer >= 2.0
- PostgreSQL >= 15

### Langkah Instalasi
```bash
# 1. Clone repository
git clone https://github.com/<owner>/nutrigrow.git
cd nutrigrow

# 2. Install dependensi
composer install

# 3. Konfigurasi environment
cp .env.example .env
php artisan key:generate

# Sesuaikan koneksi PostgreSQL pada file .env:
# DB_CONNECTION=pgsql
# DB_HOST=127.0.0.1
# DB_PORT=5432
# DB_DATABASE=nutrigrow_v2
# DB_USERNAME=postgres
# DB_PASSWORD=your_password

# 4. Jalankan migrasi dan seeder
php artisan migrate --seed

# 5. Jalankan server lokal
php artisan serve --port=9009
```
Buka peramban pada `http://localhost:9009`.

---

## 👥 Akun Akses Uji untuk Dewan Juri

Sesuai ketentuan lomba (Poin V.6), sistem menyediakan akun uji untuk setiap peran (password semua: `rahasia123`):
1. **Kader Posyandu**: `kader@nutrigrow.id` (Posyandu Melati 01)
2. **Orang Tua**: `ortu@nutrigrow.id` (Wali Balita)
3. **Tenaga Kesehatan**: `nakes@nutrigrow.id` (Dokter Pembina Puskesmas)
4. **Admin Puskesmas**: `admin@nutrigrow.id` (Petugas Gizi Puskesmas)

Tersedia tombol pemilih akun uji 1-klik di halaman masuk untuk memudahkan evaluasi dewan juri.

---

## 📄 Lisensi
Hak Cipta © 2026 Tim Pengembang NUTRIGROW.
