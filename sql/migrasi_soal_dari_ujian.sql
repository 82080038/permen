-- ============================================
-- MIGRASI SOAL & TIPS dari database ujian_sekolah_kedinasan
-- ke database skd_cat_bkn
-- ============================================

-- Gunakan database target
USE skd_cat_bkn;

-- ============================================
-- 1. MIGRASI TIPS & TRICKS
-- ============================================

-- Hapus tips lama jika ada (opsional - uncomment jika ingin replace)
-- TRUNCATE TABLE tips_tricks;

INSERT INTO tips_tricks (subtes, tipe, topik, judul, tips, contoh_soal, contoh_penerapan, contoh_jawaban, contoh_pembahasan, urutan, created_at)
SELECT 
    CASE 
        WHEN t.kategori_id = 1 THEN 'TWK'
        WHEN t.kategori_id = 2 THEN 'TIU'
        WHEN t.kategori_id = 3 THEN 'TKP'
        ELSE 'Lainnya'
    END as subtes,
    t.tipe_tips as tipe,
    COALESCE(t.tipe_tips, 'Umum') as topik, -- default ke tipe_tips atau 'Umum'
    t.judul,
    t.konten as tips,
    COALESCE(t.contoh, '') as contoh_soal,
    '' as contoh_penerapan,
    '' as contoh_jawaban,
    '' as contoh_pembahasan,
    COALESCE(t.prioritas, 0) as urutan,
    COALESCE(t.created_at, NOW()) as created_at
FROM ujian_sekolah_kedinasan.tips_tricks t
WHERE t.aktif = 1
  AND t.kategori_id IN (1, 2, 3) -- Hanya TWK, TIU, TKP
  AND NOT EXISTS (
    SELECT 1 FROM tips_tricks tt 
    WHERE tt.judul COLLATE utf8mb4_unicode_ci = t.judul COLLATE utf8mb4_unicode_ci 
    AND tt.subtes = CASE WHEN t.kategori_id=1 THEN 'TWK' WHEN t.kategori_id=2 THEN 'TIU' ELSE 'TKP' END
  );

-- ============================================
-- 2. MIGRASI SOAL
-- ============================================

-- Set variabel untuk menghindari duplikat berdasarkan pertanyaan
SET @migrated_count = 0;

-- Insert soal TWK
INSERT INTO questions (subtes, tipe, topik, pertanyaan, pilihan_a, pilihan_b, pilihan_c, pilihan_d, pilihan_e, jawaban_benar, bobot_tkp, pembahasan, created_at)
SELECT 
    'TWK' as subtes,
    NULL as tipe,
    COALESCE(lt.topic_name, s.sub_materi, 'Umum') as topik,
    s.pertanyaan,
    s.opsi_a as pilihan_a,
    s.opsi_b as pilihan_b,
    s.opsi_c as pilihan_c,
    s.opsi_d as pilihan_d,
    s.opsi_e as pilihan_e,
    s.jawaban_benar,
    NULL as bobot_tkp, -- TWK tidak pakai bobot TKP
    s.pembahasan,
    COALESCE(s.created_at, NOW()) as created_at
FROM ujian_sekolah_kedinasan.soal s
LEFT JOIN ujian_sekolah_kedinasan.learning_topics lt ON s.learning_topic_id = lt.id
WHERE s.kategori_id = 1 -- TWK
  AND s.is_duplicate = 0
  AND LENGTH(TRIM(s.pertanyaan)) > 10
  AND LENGTH(TRIM(s.jawaban_benar)) = 1
  AND s.opsi_a IS NOT NULL AND LENGTH(TRIM(s.opsi_a)) > 0
  AND s.opsi_b IS NOT NULL AND LENGTH(TRIM(s.opsi_b)) > 0
  AND s.opsi_c IS NOT NULL AND LENGTH(TRIM(s.opsi_c)) > 0
  AND s.opsi_d IS NOT NULL AND LENGTH(TRIM(s.opsi_d)) > 0
  AND NOT EXISTS (
    SELECT 1 FROM questions q 
    WHERE q.subtes = 'TWK' AND q.pertanyaan COLLATE utf8mb4_unicode_ci = s.pertanyaan COLLATE utf8mb4_unicode_ci
  )
LIMIT 300; -- Batasi 300 soal TWK

-- Insert soal TIU
INSERT INTO questions (subtes, tipe, topik, pertanyaan, pilihan_a, pilihan_b, pilihan_c, pilihan_d, pilihan_e, jawaban_benar, bobot_tkp, pembahasan, created_at)
SELECT 
    'TIU' as subtes,
    NULL as tipe,
    COALESCE(lt.topic_name, s.sub_materi, 'Umum') as topik,
    s.pertanyaan,
    s.opsi_a as pilihan_a,
    s.opsi_b as pilihan_b,
    s.opsi_c as pilihan_c,
    s.opsi_d as pilihan_d,
    s.opsi_e as pilihan_e,
    s.jawaban_benar,
    NULL as bobot_tkp, -- TIU tidak pakai bobot TKP
    s.pembahasan,
    COALESCE(s.created_at, NOW()) as created_at
FROM ujian_sekolah_kedinasan.soal s
LEFT JOIN ujian_sekolah_kedinasan.learning_topics lt ON s.learning_topic_id = lt.id
WHERE s.kategori_id = 2 -- TIU
  AND s.is_duplicate = 0
  AND LENGTH(TRIM(s.pertanyaan)) > 10
  AND LENGTH(TRIM(s.jawaban_benar)) = 1
  AND s.opsi_a IS NOT NULL AND LENGTH(TRIM(s.opsi_a)) > 0
  AND s.opsi_b IS NOT NULL AND LENGTH(TRIM(s.opsi_b)) > 0
  AND s.opsi_c IS NOT NULL AND LENGTH(TRIM(s.opsi_c)) > 0
  AND s.opsi_d IS NOT NULL AND LENGTH(TRIM(s.opsi_d)) > 0
  AND NOT EXISTS (
    SELECT 1 FROM questions q 
    WHERE q.subtes = 'TIU' AND q.pertanyaan COLLATE utf8mb4_unicode_ci = s.pertanyaan COLLATE utf8mb4_unicode_ci
  )
LIMIT 400; -- Batasi 400 soal TIU

-- Insert soal TKP
INSERT INTO questions (subtes, tipe, topik, pertanyaan, pilihan_a, pilihan_b, pilihan_c, pilihan_d, pilihan_e, jawaban_benar, bobot_tkp, pembahasan, created_at)
SELECT 
    'TKP' as subtes,
    NULL as tipe,
    COALESCE(lt.topic_name, s.sub_materi, 'Umum') as topik,
    s.pertanyaan,
    s.opsi_a as pilihan_a,
    s.opsi_b as pilihan_b,
    s.opsi_c as pilihan_c,
    s.opsi_d as pilihan_d,
    s.opsi_e as pilihan_e,
    s.jawaban_benar,
    CASE 
        WHEN s.jawaban_benar = 'A' THEN 5
        WHEN s.jawaban_benar = 'B' THEN 4
        WHEN s.jawaban_benar = 'C' THEN 3
        WHEN s.jawaban_benar = 'D' THEN 2
        WHEN s.jawaban_benar = 'E' THEN 1
        ELSE NULL
    END as bobot_tkp,
    s.pembahasan,
    COALESCE(s.created_at, NOW()) as created_at
FROM ujian_sekolah_kedinasan.soal s
LEFT JOIN ujian_sekolah_kedinasan.learning_topics lt ON s.learning_topic_id = lt.id
WHERE s.kategori_id = 3 -- TKP
  AND s.is_duplicate = 0
  AND LENGTH(TRIM(s.pertanyaan)) > 10
  AND LENGTH(TRIM(s.jawaban_benar)) = 1
  AND s.opsi_a IS NOT NULL AND LENGTH(TRIM(s.opsi_a)) > 0
  AND s.opsi_b IS NOT NULL AND LENGTH(TRIM(s.opsi_b)) > 0
  AND s.opsi_c IS NOT NULL AND LENGTH(TRIM(s.opsi_c)) > 0
  AND s.opsi_d IS NOT NULL AND LENGTH(TRIM(s.opsi_d)) > 0
  AND NOT EXISTS (
    SELECT 1 FROM questions q 
    WHERE q.subtes = 'TKP' AND q.pertanyaan COLLATE utf8mb4_unicode_ci = s.pertanyaan COLLATE utf8mb4_unicode_ci
  )
LIMIT 200; -- Batasi 200 soal TKP

-- ============================================
-- 3. MIGRASI OPSI SOAL ke question_options (NORMALISASI)
-- ============================================

-- Insert opsi untuk soal yang baru dimigrasi
INSERT INTO question_options (question_id, opsi_label, opsi_teks)
SELECT 
    q.id,
    'A',
    q.pilihan_a
FROM questions q
LEFT JOIN question_options qo ON q.id = qo.question_id AND qo.opsi_label = 'A'
WHERE qo.id IS NULL AND q.pilihan_a IS NOT NULL AND q.pilihan_a != '';

INSERT INTO question_options (question_id, opsi_label, opsi_teks)
SELECT 
    q.id,
    'B',
    q.pilihan_b
FROM questions q
LEFT JOIN question_options qo ON q.id = qo.question_id AND qo.opsi_label = 'B'
WHERE qo.id IS NULL AND q.pilihan_b IS NOT NULL AND q.pilihan_b != '';

INSERT INTO question_options (question_id, opsi_label, opsi_teks)
SELECT 
    q.id,
    'C',
    q.pilihan_c
FROM questions q
LEFT JOIN question_options qo ON q.id = qo.question_id AND qo.opsi_label = 'C'
WHERE qo.id IS NULL AND q.pilihan_c IS NOT NULL AND q.pilihan_c != '';

INSERT INTO question_options (question_id, opsi_label, opsi_teks)
SELECT 
    q.id,
    'D',
    q.pilihan_d
FROM questions q
LEFT JOIN question_options qo ON q.id = qo.question_id AND qo.opsi_label = 'D'
WHERE qo.id IS NULL AND q.pilihan_d IS NOT NULL AND q.pilihan_d != '';

INSERT INTO question_options (question_id, opsi_label, opsi_teks)
SELECT 
    q.id,
    'E',
    q.pilihan_e
FROM questions q
LEFT JOIN question_options qo ON q.id = qo.question_id AND qo.opsi_label = 'E'
WHERE qo.id IS NULL AND q.pilihan_e IS NOT NULL AND q.pilihan_e != '';

-- ============================================
-- 4. STATISTIK MIGRASI
-- ============================================
SELECT 'TWK' as subtes, COUNT(*) as jumlah FROM questions WHERE subtes = 'TWK' UNION ALL
SELECT 'TIU', COUNT(*) FROM questions WHERE subtes = 'TIU' UNION ALL
SELECT 'TKP', COUNT(*) FROM questions WHERE subtes = 'TKP';
