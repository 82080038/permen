<?php
/**
 * Smart Soal Generator — Tanpa API Eksternal
 * 
 * Menggunakan algoritma PHP + template dari master_materi untuk generate soal.
 * Tidak memerlukan API key, internet, atau AI pihak ketiga.
 * 
 * Cara pakai:
 *   php api/generate_soal_smart.php?subtes=TIU&tipe=numerik&topik=Deret+Angka&jumlah=10
 * 
 * Atau via browser:
 *   http://localhost/permen/api/generate_soal_smart.php?subtes=TIU&tipe=numerik&topik=Deret+Angka&jumlah=10
 */

require '../config.php';
header('Content-Type: application/json; charset=utf-8');

// Include generator modules
require_once __DIR__ . '/generators/helpers.php';
require_once __DIR__ . '/generators/tiu_generator.php';
require_once __DIR__ . '/generators/twk_generator.php';

// Guard: admin only
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Akses ditolak. Hanya admin yang bisa generate soal.']);
    exit;
}

$subtes    = $_GET['subtes']   ?? 'TIU';
$tipe      = $_GET['tipe']     ?? 'numerik';
$topik     = $_GET['topik']    ?? 'Deret Angka';
$jumlah    = (int)($_GET['jumlah'] ?? 5);
$kesulitan = $_GET['kesulitan'] ?? 'sedang';
$imageUrl  = $_GET['image_url'] ?? null; // path gambar soal, e.g. assets/soal/xxx.svg

// Ambil master materi
$stmt = $pdo->prepare("SELECT id, kisi_kisi FROM master_materi WHERE subtes = ? AND tipe = ? AND topik = ? LIMIT 1");
$stmt->execute([$subtes, $tipe, $topik]);
$master = $stmt->fetch();

if (!$master) {
    http_response_code(404);
    echo json_encode(['error' => 'Master materi tidak ditemukan: ' . $subtes . '/' . $tipe . '/' . $topik]);
    exit;
}

$masterId = $master['id'];
$soalGenerated = [];

// ============================================================
// HELPERS: materi mapping
// ============================================================

// Build materi lookup map
$materiMap = [];
$stmtM = $pdo->query("SELECT id, subtes, judul FROM materi");
foreach ($stmtM as $rm) {
    $key = strtolower($rm['subtes'] . '_' . $rm['judul']);
    $materiMap[$key] = $rm['id'];
}

$topicToMateri = [
    'logika matematika' => $materiMap['tiu_logika matematika: aritmatika & geometri'] ?? null,
    'berhitung' => $materiMap['tiu_berhitung cepat'] ?? null,
    'deret angka' => $materiMap['tiu_deret angka: aritmatika & geometri'] ?? null,
    'perbandingan' => $materiMap['tiu_perbandingan & proporsi'] ?? null,
    'ketidaksamaan' => $materiMap['tiu_figural: serial & ketidaksamaan'] ?? null,
    'analogi' => $materiMap['tiu_analogi verbal'] ?? null,
    'serial' => $materiMap['tiu_figural: serial & ketidaksamaan'] ?? null,
    'analitis' => $materiMap['tiu_analitis & penalaran'] ?? null,
    'silogisme' => $materiMap['tiu_silogisme'] ?? null,
    'soal cerita' => $materiMap['tiu_soal cerita matematika'] ?? null,
    'nasionalisme' => $materiMap['twk_nasionalisme & pahlawan'] ?? null,
    'sejarah' => $materiMap['twk_sejarah kemerdekaan indonesia'] ?? null,
    'pancasila' => $materiMap['twk_pancasila: sila & lambang'] ?? null,
    'bahasa indonesia' => $materiMap['twk_bahasa indonesia baku'] ?? null,
    'uud 1945' => $materiMap['twk_uud 1945 & amandemen'] ?? null,
    'pilar negara' => $materiMap['twk_pilar negara & bhinneka tunggal ika'] ?? null,
    'integritas' => $materiMap['twk_integritas & anti korupsi'] ?? null,
    'bela negara' => $materiMap['twk_bela negara'] ?? null,
    'kepribadian' => $materiMap['tkp_kepribadian & kecerdasan emosional'] ?? null,
    'pelayanan publik' => $materiMap['tkp_pelayanan publik prima'] ?? null,
    'jejaring kerja' => $materiMap['tkp_jejaring kerja & kerja sama'] ?? null,
    'sosial budaya' => $materiMap['tkp_sosial budaya & keberagaman'] ?? null,
    'profesionalisme' => $materiMap['tkp_profesionalisme & integritas'] ?? null,
    'teknologi informasi' => $materiMap['tkp_teknologi informasi & adaptasi'] ?? null,
];

$materiId = $topicToMateri[strtolower($topik)] ?? null;

// ============================================================
// GENERATOR FUNCTIONS - Now loaded from separate modules
// ============================================================
// All generator functions are now loaded from:
// - generators/helpers.php (buildTips, buildLinks, expandPembahasan, randomInt, shuffleAssoc)
// - generators/tiu_generator.php (generateDeretAngka, generateBerhitung, generatePerbandingan, generateSoalCerita, generateTIU_PassageLogika)
// - generators/twk_generator.php (generateTWK_Nasionalisme, generateTWK)
// - generators/tkp_generator.php (generateTKP)
//
// This file now only contains the dispatcher logic and database insertion code.

// ============================================================
// DISPATCHER
// ============================================================
$mode = $_GET['mode'] ?? 'single'; // 'single' atau 'passage'
$passageData = null;

for ($i = 0; $i < $jumlah; $i++) {
    if ($mode === 'passage' && $subtes === 'TIU' && $topik === 'Logika Matematika') {
        // Mode passage: generate sekali, ambil soal-nya
        if (!$passageData) {
            $passageData = generateTIU_PassageLogika();
        }
        $s = $passageData['soal'][$i] ?? $passageData['soal'][array_rand($passageData['soal'])];
    } elseif ($subtes === 'TIU' && $tipe === 'numerik' && $topik === 'Deret Angka') {
        $s = generateDeretAngka($kesulitan);
    } elseif ($subtes === 'TIU' && $tipe === 'numerik' && $topik === 'Berhitung') {
        $s = generateBerhitung($kesulitan);
    } elseif ($subtes === 'TIU' && $tipe === 'numerik' && $topik === 'Perbandingan') {
        $s = generatePerbandingan();
    } elseif ($subtes === 'TIU' && $tipe === 'numerik' && $topik === 'Soal Cerita') {
        $s = generateSoalCerita();
    } elseif ($subtes === 'TWK' && $topik === 'Nasionalisme') {
        $s = generateTWK_Nasionalisme();
    } elseif ($subtes === 'TWK') {
        $s = generateTWK();
    } elseif ($subtes === 'TKP') {
        $s = generateTKP();
    } else {
        // Default fallback: deret angka
        $s = generateDeretAngka($kesulitan);
    }

    $soalGenerated[] = $s;
}

// ============================================================
// SIMPAN KE DATABASE
// ============================================================
$inserted = 0;
$skippedDup = 0;
$passageId = null;

$tips = buildTips($subtes, $topik);
$links = buildLinks($subtes, $topik);

$stmtCache = $pdo->prepare("INSERT INTO soal_ai_cache (master_materi_id, pertanyaan, pilihan_a, pilihan_b, pilihan_c, pilihan_d, pilihan_e, jawaban_benar, bobot_tkp, pembahasan, image_url, tingkat_kesulitan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmtQ = $pdo->prepare("INSERT INTO questions (subtes, tipe, topik, pertanyaan, pilihan_a, pilihan_b, pilihan_c, pilihan_d, pilihan_e, jawaban_benar, bobot_tkp, pembahasan, tips_trick, related_links, materi_id, image_url, passage_id, passage_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmtCheckDup = $pdo->prepare("SELECT id FROM questions WHERE pertanyaan = ? LIMIT 1");

// Insert passage terlebih dahulu jika mode passage
if ($mode === 'passage' && $passageData) {
    $stmtPassage = $pdo->prepare("INSERT INTO passages (subtes, tipe, topik, judul, bacaan) VALUES (?, ?, ?, ?, ?)");
    $stmtPassage->execute([$subtes, $tipe, $topik, $passageData['passage']['judul'], $passageData['passage']['bacaan']]);
    $passageId = $pdo->lastInsertId();
}

foreach ($soalGenerated as $order => $s) {
    $bobot = $s['bobot_tkp'] ?? NULL;
    $pemb = expandPembahasan($subtes, $topik, $s['pertanyaan'], $s['jawaban_benar'], $s['pembahasan']);

    // Deduplicate: skip jika pertanyaan sudah ada
    $stmtCheckDup->execute([$s['pertanyaan']]);
    if ($stmtCheckDup->fetch()) {
        $skippedDup++;
        continue;
    }

    try {
        $stmtCache->execute([
            $masterId, $s['pertanyaan'], $s['pilihan_a'], $s['pilihan_b'], $s['pilihan_c'],
            $s['pilihan_d'], $s['pilihan_e'], $s['jawaban_benar'], $bobot, $pemb, $imageUrl, $kesulitan
        ]);

        $stmtQ->execute([
            $subtes, $tipe, $topik, $s['pertanyaan'], $s['pilihan_a'], $s['pilihan_b'],
            $s['pilihan_c'], $s['pilihan_d'], $s['pilihan_e'], $s['jawaban_benar'], $bobot, $pemb,
            $tips, $links, $materiId, $imageUrl, $passageId, ($passageId ? $order + 1 : 0)
        ]);

        $inserted++;
    } catch (Exception $e) {
        // Skip jika gagal (misalnya race condition duplikat)
    }
}

echo json_encode([
    'success' => true,
    'generator' => 'smart_internal',
    'no_api_required' => true,
    'mode' => $mode,
    'generated' => count($soalGenerated),
    'inserted' => $inserted,
    'skipped_duplicate' => $skippedDup,
    'passage_id' => $passageId,
    'subtes' => $subtes,
    'tipe' => $tipe,
    'topik' => $topik,
    'kesulitan' => $kesulitan,
    'soal' => $soalGenerated
]);

// Log admin action
require '../helpers.php';
logAdminAction($userId, 'GENERATE_QUESTIONS', null, null, json_encode(['subtes' => $subtes, 'tipe' => $tipe, 'topik' => $topik, 'jumlah' => $jumlah, 'generated' => $inserted]));
