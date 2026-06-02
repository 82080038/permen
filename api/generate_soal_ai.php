<?php
/**
 * AI Soal Generator — Batch via Gemini API
 * 
 * Cara pakai:
 * 1. Dapatkan API key gratis di https://aistudio.google.com/app/apikey
 * 2. Edit $GEMINI_API_KEY di bawah ini.
 * 3. Jalankan via browser atau CLI:
 *    php api/generate_soal_ai.php?subtes=TIU&tipe=numerik&topik=Deret+Angka&jumlah=10
 * 
 * Batasan Gemini 2.0 Flash gratis: 1.500 req/hari, 15 req/menit.
 */

require '../config.php';
header('Content-Type: application/json; charset=utf-8');

// Guard: admin only
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Akses ditolak. Hanya admin yang bisa generate soal.']);
    exit;
}

// === KONFIGURASI ===
$GEMINI_API_KEY = $_ENV['GEMINI_API_KEY'] ?? 'YOUR_API_KEY_HERE';
$GEMINI_MODEL   = 'gemini-2.0-flash';

// === PARAMETER ===
$subtes   = $_GET['subtes']   ?? 'TIU';
$tipe     = $_GET['tipe']     ?? '';
$topik    = $_GET['topik']    ?? 'Deret Angka';
$jumlah   = (int)($_GET['jumlah'] ?? 5);
$kesulitan = $_GET['kesulitan'] ?? 'sedang';
$mode      = $_GET['mode']      ?? 'single'; // 'single' atau 'passage'
$imageUrl  = $_GET['image_url'] ?? null; // path gambar soal

if ($GEMINI_API_KEY === 'YOUR_API_KEY_HERE') {
    echo json_encode(['error' => 'API key Gemini belum diatur. Edit file ini dan isi $GEMINI_API_KEY.']);
    exit;
}

// Ambil kisi-kisi dari master_materi
$stmt = $pdo->prepare("SELECT kisi_kisi FROM master_materi WHERE subtes = ? AND tipe = ? AND topik = ? LIMIT 1");
$stmt->execute([$subtes, $tipe, $topik]);
$master = $stmt->fetch();

if (!$master) {
    echo json_encode(['error' => 'Master materi tidak ditemukan untuk ' . $subtes . '/' . $tipe . '/' . $topik]);
    exit;
}

$kisi = $master['kisi_kisi'];

// Susun prompt
if ($mode === 'passage') {
    $prompt = "Buatkan 1 bacaan/narasi (passage) dan {$jumlah} soal pilihan ganda (A-E) berdasarkan bacaan tersebut untuk tes SKD Sekolah Kedinasan.
Subtes: {$subtes}
Tipe: {$tipe}
Topik: {$topik}
Tingkat kesulitan: {$kesulitan}
Acuan materi: {$kisi}

Format output HARUS JSON persis seperti ini:
{
  \"judul\": \"Judul bacaan\",
  \"bacaan\": \"Teks narasi/bacaan panjang yang menjadi dasar pertanyaan...\",
  \"soal\": [
    {
      \"pertanyaan\": \"Pertanyaan berdasarkan bacaan\",
      \"pilihan_a\": \"teks opsi A\",
      \"pilihan_b\": \"teks opsi B\",
      \"pilihan_c\": \"teks opsi C\",
      \"pilihan_d\": \"teks opsi D\",
      \"pilihan_e\": \"teks opsi E\",
      \"jawaban_benar\": \"A\",
      \"pembahasan\": \"pembahasan singkat\"
    }
  ]
}

Aturan penting:
1. Bacaan cukup panjang untuk menghasilkan {$jumlah} pertanyaan.
2. Setiap pertanyaan HARUS bisa dijawab hanya dari bacaan.
3. Jawaban tidak boleh ambigu.
4. Hanya 1 jawaban yang benar per soal.
5. Pembahasan singkat padat.
6. Output HANYA JSON, tanpa teks penjelasan di luar JSON.";
} else {
    $prompt = "Buatkan {$jumlah} soal pilihan ganda (A-E) untuk tes SKD Sekolah Kedinasan.
Subtes: {$subtes}
Tipe: {$tipe}
Topik: {$topik}
Tingkat kesulitan: {$kesulitan}
Acuan materi: {$kisi}

Format output HARUS JSON array dengan struktur persis seperti ini:
[
  {
    \"pertanyaan\": \"teks soal\",
    \"pilihan_a\": \"teks opsi A\",
    \"pilihan_b\": \"teks opsi B\",
    \"pilihan_c\": \"teks opsi C\",
    \"pilihan_d\": \"teks opsi D\",
    \"pilihan_e\": \"teks opsi E\",
    \"jawaban_benar\": \"A\",
    \"pembahasan\": \"pembahasan singkat\"
  }
]

Aturan penting:
1. Jawaban tidak boleh ambigu.
2. Hanya 1 jawaban yang benar.
3. Soal berbeda satu sama lain.
4. Pembahasan singkat padat.
5. Output HANYA JSON, tanpa teks penjelasan di luar JSON.";
}

// Kirim ke Gemini API
$url = "https://generativelanguage.googleapis.com/v1beta/models/{$GEMINI_MODEL}:generateContent?key={$GEMINI_API_KEY}";

$postData = json_encode([
    'contents' => [
        ['parts' => [['text' => $prompt]]]
    ],
    'generationConfig' => [
        'temperature' => 0.7,
        'maxOutputTokens' => 8192
    ]
]);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);

$response = curl_exec($ch);
$err = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($err) {
    echo json_encode(['error' => 'cURL error: ' . $err]);
    exit;
}

if ($httpCode !== 200) {
    echo json_encode(['error' => 'Gemini API HTTP ' . $httpCode, 'raw' => $response]);
    exit;
}

$data = json_decode($response, true);
$text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

// Bersihkan markdown code block
$text = preg_replace('/^```json\s*|\s*```$/m', '', $text);
$text = trim($text);

$parsed = json_decode($text, true);
if (!$parsed || !is_array($parsed)) {
    echo json_encode(['error' => 'Gagal parse JSON dari AI', 'raw' => $text]);
    exit;
}

// Normalisasi: passage mode = object {judul, bacaan, soal: [...]}, single mode = array [...]
$passageData = null;
$soalArray = [];

if ($mode === 'passage') {
    if (empty($parsed['soal']) || !is_array($parsed['soal'])) {
        echo json_encode(['error' => 'Format passage tidak valid: key soal tidak ditemukan', 'raw' => $text]);
        exit;
    }
    $passageData = [
        'judul' => $parsed['judul'] ?? ($topik . ' — Bacaan'),
        'bacaan' => $parsed['bacaan'] ?? ''
    ];
    $soalArray = $parsed['soal'];
} else {
    $soalArray = $parsed;
}

// Simpan ke soal_ai_cache dan questions
$inserted = 0;
$skippedDup = 0;
$passageId = null;

$stmt = $pdo->prepare("INSERT INTO soal_ai_cache 
    (master_materi_id, pertanyaan, pilihan_a, pilihan_b, pilihan_c, pilihan_d, pilihan_e, jawaban_benar, pembahasan, image_url, tingkat_kesulitan)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmtQ = $pdo->prepare("INSERT INTO questions 
    (subtes, tipe, topik, pertanyaan, pilihan_a, pilihan_b, pilihan_c, pilihan_d, pilihan_e, jawaban_benar, pembahasan, image_url, passage_id, passage_order)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmtCheckDup = $pdo->prepare("SELECT id FROM questions WHERE pertanyaan = ? LIMIT 1");

// Dapatkan master_materi_id
$stmtM = $pdo->prepare("SELECT id FROM master_materi WHERE subtes = ? AND tipe = ? AND topik = ? LIMIT 1");
$stmtM->execute([$subtes, $tipe, $topik]);
$masterId = $stmtM->fetchColumn();

// Insert passage terlebih dahulu jika mode passage
if ($mode === 'passage' && $passageData) {
    $stmtPassage = $pdo->prepare("INSERT INTO passages (subtes, tipe, topik, judul, bacaan) VALUES (?, ?, ?, ?, ?)");
    $stmtPassage->execute([$subtes, $tipe, $topik, $passageData['judul'], $passageData['bacaan']]);
    $passageId = $pdo->lastInsertId();
}

foreach ($soalArray as $order => $s) {
    // Deduplicate: skip jika pertanyaan sudah ada
    $stmtCheckDup->execute([$s['pertanyaan']]);
    if ($stmtCheckDup->fetch()) {
        $skippedDup++;
        continue;
    }

    try {
        // Simpan ke cache AI
        $stmt->execute([
            $masterId,
            $s['pertanyaan'],
            $s['pilihan_a'],
            $s['pilihan_b'],
            $s['pilihan_c'],
            $s['pilihan_d'],
            $s['pilihan_e'],
            $s['jawaban_benar'],
            $s['pembahasan'],
            $imageUrl,
            $kesulitan
        ]);

        // Simpan ke bank soal utama
        $stmtQ->execute([
            $subtes, $tipe, $topik,
            $s['pertanyaan'],
            $s['pilihan_a'], $s['pilihan_b'], $s['pilihan_c'], $s['pilihan_d'], $s['pilihan_e'],
            $s['jawaban_benar'], $s['pembahasan'],
            $imageUrl, $passageId, ($passageId ? $order + 1 : 0)
        ]);

        $inserted++;
    } catch (Exception $e) {
        // Skip soal yang gagal insert
    }
}

$responseData = [
    'success' => true,
    'mode' => $mode,
    'generated' => count($soalArray),
    'inserted' => $inserted,
    'skipped_duplicate' => $skippedDup,
    'subtes' => $subtes,
    'topik' => $topik,
    'kesulitan' => $kesulitan,
    'soal' => $soalArray
];
if ($passageId) {
    $responseData['passage_id'] = $passageId;
    $responseData['passage_judul'] = $passageData['judul'];
}
echo json_encode($responseData);
