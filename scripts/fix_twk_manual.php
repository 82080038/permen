<?php
/**
 * Manual fix for TWK generator - replace jawaban_benar letters with text
 */

$file = 'C:\xampp\htdocs\permen\api\generators\twk_generator.php';
$content = file_get_contents($file);

// Manual replacements for each template
$replacements = [
    // Nasionalisme
    ["'jawaban_benar' => 'A'", "'jawaban_benar' => 'Menggunakan produk dalam negeri dan mendukung UMKM'"],
    ["'jawaban_benar' => 'B'", "'jawaban_benar' => '20 Mei'"],
    ["'jawaban_benar' => 'C'", "'jawaban_benar' => 'Kepala banteng'"],
    ["'jawaban_benar' => 'A'", "'jawaban_benar' => 'Jakarta'"],
    ["'jawaban_benar' => 'B'", "'jawaban_benar' => 'Bandung'"],
    ["'jawaban_benar' => 'B'", "'jawaban_benar' => '16 Agustus 1945'"],
    ["'jawaban_benar' => 'C'", "'jawaban_benar' => 'Ernest Douwes Dekker'"],
    ["'jawaban_benar' => 'B'", "'jawaban_benar' => 'Bung Tomo'"],
    ["'jawaban_benar' => 'B'", "'jawaban_benar' => 'UUD 1945'"],
    ["'jawaban_benar' => 'C'", "'jawaban_benar' => 'Bebas menentukan sikap sesuai kepentingan nasional tanpa memihak blok mana pun'"],
    ["'jawaban_benar' => 'C'", "'jawaban_benar' => 'KPU'"],
    ["'jawaban_benar' => 'B'", "'jawaban_benar' => '1965'"],
    ["'jawaban_benar' => 'C'", "'jawaban_benar' => 'Pancasila Sila Ketiga'"],
    ["'jawaban_benar' => 'C'", "'jawaban_benar' => 'Aisyiyah'"],
    ["'jawaban_benar' => 'D'", "'jawaban_benar' => 'Kerakyatan yang dipimpin oleh hikmat kebijaksanaan dalam permusyawaratan/perwakilan'"],
    ["'jawaban_benar' => 'D'", "'jawaban_benar' => 'Berdaulat di bidang militer'"],
    ["'jawaban_benar' => 'C'", "'jawaban_benar' => '1967'"],
    ["'jawaban_benar' => 'B'", "'jawaban_benar' => 'Meningkatkan pemerataan penduduk dan pembangunan antarwilayah'"],
    ["'jawaban_benar' => 'C'", "'jawaban_benar' => '29 April 1945'"],
    ["'jawaban_benar' => 'D'", "'jawaban_benar' => 'Sayuti Melik'"],
    
    // Pancasila/Pilar Negara
    ["'jawaban_benar' => 'A'", "'jawaban_benar' => 'Ketuhanan Yang Maha Esa'"],
    ["'jawaban_benar' => 'C'", "'jawaban_benar' => '4'"],
    ["'jawaban_benar' => 'B'", "'jawaban_benar' => 'Pohon beringin'"],
    ["'jawaban_benar' => 'C'", "'jawaban_benar' => 'Ekonomi & keuangan negara'"],
    ["'jawaban_benar' => 'B'", "'jawaban_benar' => 'Pembukaan UUD 1945'"],
    ["'jawaban_benar' => 'C'", "'jawaban_benar' => 'Sutasoma'"],
    ["'jawaban_benar' => 'C'", "'jawaban_benar' => '4 pilar'"],
    ["'jawaban_benar' => 'B'", "'jawaban_benar' => 'MK'"],
    ["'jawaban_benar' => 'C'", "'jawaban_benar' => 'DPR dan DPD'"],
    ["'jawaban_benar' => 'C'", "'jawaban_benar' => 'Kesatuan'"],
];

foreach ($replacements as $replace) {
    $content = str_replace($replace[0], $replace[1], $content);
}

file_put_contents($file, $content);
echo "TWK generator fixed!\n";
