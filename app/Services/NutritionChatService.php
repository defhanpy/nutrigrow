<?php

namespace App\Services;

use App\Models\Child;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NutritionChatService
{
    protected string $apiBase;
    protected ?string $apiKey;
    protected string $model;

    public function __construct()
    {
        $this->apiBase = rtrim(env('AI_API_BASE', 'http://127.0.0.1:30129/v1'), '/');
        $this->apiKey = env('AI_API_KEY');
        $this->model = env('AI_MODEL', 'backup');
    }

    /**
     * Balas pesan dari pengguna dengan memadukan data anak, riwayat chat, dan LLM/Fallback.
     */
    public function reply(User $user, string $userMessage, ?Child $child = null): string
    {
        // 1. Simpan pesan user
        ChatMessage::create([
            'user_id' => $user->id,
            'child_id' => $child?->id,
            'role' => 'user',
            'message' => $userMessage,
        ]);

        // 2. Susun prompt sistem dan konteks percakapan
        $systemPrompt = $this->buildSystemPrompt($user, $child);

        // Ambil riwayat 6 pesan terakhir untuk memori percakapan
        $history = ChatMessage::where('user_id', $user->id)
            ->latest('id')
            ->take(6)
            ->get()
            ->reverse()
            ->map(fn ($m) => [
                'role' => $m->role,
                'content' => $m->message,
            ])
            ->values()
            ->toArray();

        $messages = array_merge([
            ['role' => 'system', 'content' => $systemPrompt]
        ], $history);

        // 3. Panggil API AI Gateway
        $replyText = $this->callAiGateway($messages);

        // 4. Jika AI gateway gagal / timeout / kuota habis, gunakan fallback cerdas berbasis panduan gizi lokal
        if (empty($replyText)) {
            $replyText = $this->generateLocalFallback($userMessage, $child);
        }

        // 5. Simpan balasan assistant
        ChatMessage::create([
            'user_id' => $user->id,
            'child_id' => $child?->id,
            'role' => 'assistant',
            'message' => $replyText,
        ]);

        return $replyText;
    }

    protected function buildSystemPrompt(User $user, ?Child $child): string
    {
        $prompt = "Kamu adalah NutriBot, asisten cerdas gizi anak dan balita pada platform NUTRIGROW (Standar Baku WHO & Posyandu Komunitas).\n";
        $prompt .= "Tugasmu: membantu kader Posyandu dan orang tua memahami menu MPASI, mengatasi anak susah makan (GTM), memanfaatkan pangan lokal bergizi terjangkau, dan menjelaskan kurva pertumbuhan KMS secara ramah.\n";
        $prompt .= "Aturan Ketat:\n";
        $prompt .= "1. BUKAN alat diagnosis medis. Jangan pernah menyatakan diagnosis penyakit spesifik atau meresepkan obat/suplemen kimia keras.\n";
        $prompt .= "2. Jika pengguna menyebutkan tanda bahaya (demam tinggi, anak lemas/tidak mau minum, muntah terus menerus, diare akut, sesak napas), WAJIB segera sarankan periksa ke dokter/Puskesmas terdekat.\n";
        $prompt .= "3. Utamakan bahan pangan lokal Indonesia yang terjangkau: daun kelor, telur ayam, tempe, tahu, ikan lele, ikan kembung (kaya omega 3 pengganti salmon), hati ayam (tinggi zat besi cegah anemia), santan (tambahan lemak baik).\n";
        $prompt .= "4. Gaya bahasa: Indonesia santun, ramah, menenangkan, empati, jelas dan terstruktur dengan poin-poin rapi.\n";

        if ($child) {
            $ageMonths = $child->ageInMonths();
            $status = $child->latestMeasurement?->assessment?->overall_status ?? 'Belum ada data';
            $prompt .= "\nKonteks Balita Saat Ini:\n";
            $prompt .= "- Nama: {$child->full_name}\n";
            $prompt .= "- Usia: {$ageMonths} bulan\n";
            $prompt .= "- Jenis Kelamin: " . ($child->sex->value === 'L' ? 'Laki-laki' : 'Perempuan') . "\n";
            $prompt .= "- Status Penilaian Terakhir: {$status}\n";
            $prompt .= "Sesuaikan saran tekstur, porsi, dan kebutuhan nutrisi dengan usia balita ini.";
        }

        return $prompt;
    }

    protected function callAiGateway(array $messages): ?string
    {
        if (empty($this->apiKey) || empty($this->apiBase)) {
            return null;
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(12)
                ->post("{$this->apiBase}/chat/completions", [
                    'model' => $this->model,
                    'stream' => false,
                    'messages' => $messages,
                    'temperature' => 0.7,
                ]);

            if ($response->successful()) {
                $content = $response->json('choices.0.message.content');
                if (!empty($content)) {
                    return trim($content);
                }
            } else {
                Log::warning("AI Gateway returned status: " . $response->status() . " - " . $response->body());
            }
        } catch (\Throwable $e) {
            Log::info("AI Gateway unavailable or timed out: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Fallback cerdas berbasis panduan resmi Kemenkes & WHO jika LLM sedang offline/kuota habis.
     */
    protected function generateLocalFallback(string $query, ?Child $child): string
    {
        $q = strtolower($query);
        $childName = $child ? $child->full_name : 'si kecil';
        $age = $child ? $child->ageInMonths() : null;

        // Tanda bahaya
        if (preg_match('/(demam|panas tinggi|muntah|diare|mencret|kejang|lemas|sesak)/i', $q)) {
            return "⚠️ **Perhatian Penting**: Jika {$childName} mengalami demam tinggi, diare berulang, muntah terus-menerus, atau tampak lemas dan tidak mau minum, mohon segera bawa ke **Puskesmas atau fasilitas kesehatan terdekat** untuk pemeriksaan dokter secara langsung. Pastikan cairan tubuh tetap terjaga dengan memberikan ASI atau oralit sedikit demi sedikit sembari menuju layanan kesehatan.";
        }

        // GTM / Susah makan
        if (preg_match('/(gtm|susah makan|gamau makan|tidak mau makan|ogah makan|pilih makan)/i', $q)) {
            return "Halo Bunda/Ayah! Menghadapi fase GTM (Gerakan Tutup Mulut) memang butuh kesabaran ekstra. Berikut langkah praktis yang dianjurkan panduan Posyandu:\n\n" .
                "1. **Terapkan Feeding Rules**: Berikan makan teratur maksimal 30 menit per sesi. Jangan memaksa anak jika sudah kenyang/menolak.\n" .
                "2. **Variasi Tekstur & Bentuk**: Coba ubah bentuk sajian, misalnya nasi tim dibuat bola-bola nasi, perkedel tempe telur, atau finger food yang bisa digenggam sendiri.\n" .
                "3. **Perkaya Lemak & Rasa Alami**: Tambahkan sedikit santan, minyak kelapa, atau mentega pada makanan agar lebih gurih dan padat kalori.\n" .
                "4. **Ciptakan Suasana Menyenangkan**: Hindari distraksi gadget/layar saat makan, dan ajak makan bersama anggota keluarga lain di meja makan.";
        }

        // Zat besi / Cegah Anemia & Stunting
        if (preg_match('/(zat besi|anemia|stunting|hati|salmon|kembung)/i', $q)) {
            return "Bahan pangan lokal Indonesia sangat kaya zat besi dan protein berkualitas tinggi untuk mendukung pertumbuhan {$childName}:\n\n" .
                "• **Hati Ayam**: Sumber zat besi heme terbaik yang sangat mudah diserap tubuh anak untuk mencegah stunting dan anemia.\n" .
                "• **Ikan Kembung**: Mengandung omega-3 (DHA/EPA) yang bahkan setara atau lebih tinggi dari salmon impor, dengan harga yang sangat terjangkau.\n" .
                "• **Telur Ayam**: Protein hewani lengkap dengan asam amino esensial. Sangat dianjurkan minimal 1 butir telur per hari.\n" .
                "• **Sayur Daun Kelor**: Kaya vitamin A, zat besi nabati, dan kalsium. Sajikan bersama sumber vitamin C seperti tomat atau jeruk agar penyerapan zat besi maksimal.";
        }

        // KMS / Garis pertumbuhan
        if (preg_match('/(kms|garis kuning|garis merah|z score|kurva)/i', $q)) {
            return "Grafik pertumbuhan KMS (Kartu Menuju Sehat) standar WHO memantau tren kenaikan berat dan tinggi badan anak:\n\n" .
                "• **Garis Hijau (Median / ±2 SD)**: Menunjukkan pertumbuhan normal dan ideal.\n" .
                "• **Garis Kuning (±2 hingga ±3 SD)**: Menandakan berat badan kurang atau risiko gizi lebih yang perlu dipantau lebih cermat pada penimbangan bulan berikutnya.\n" .
                "• **Di bawah Garis Merah (<-3 SD)**: Memerlukan penanganan dan rujukan segera ke Puskesmas untuk evaluasi gizi intensif.\n\n" .
                "Hal terpenting adalah **arah garis kurva**: berat badan harus terus naik mengikuti pita pertumbuhan usianya (tidak mendatar atau menurun).";
        }

        // Default MPASI / Menu Gizi
        $ageContext = $age ? "untuk usia {$age} bulan" : "sesuai usia balita";
        return "Terima kasih atas pertanyaannya! Untuk pemenuhan gizi {$childName} {$ageContext}, berikut panduan menu seimbang berbahan pangan lokal:\n\n" .
            "• **Karbohidrat**: Nasi tim lembek, ubi jalar, kentang, atau bubur jagung manis.\n" .
            "• **Protein Hewani (Prioritas Utama)**: Telur, ikan lele, ikan kembung, daging ayam, atau hati ayam.\n" .
            "• **Protein Nabati**: Tempe dan tahu lumat sebagai pelengkap serat & kalsium.\n" .
            "• **Lemak Tambahan**: Santan segar atau sedikit minyak kelapa untuk menambah kalori.\n\n" .
            "Ada hal spesifik yang ingin Bunda tanyakan seputar porsi, jadwal makan, atau bahan makanan tertentu?";
    }
}
