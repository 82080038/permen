-- Update tipe untuk soal yang NULL berdasarkan subtes dan topik
-- Jalankan di phpMyAdmin atau via command line

-- Update TKP (semua TKP adalah tipe pribadi/karakter)
UPDATE questions SET tipe = 'pribadi' WHERE subtes = 'TKP' AND tipe IS NULL;

-- Update TWK (semua TWK adalah tipe wawasan)
UPDATE questions SET tipe = 'wawasan' WHERE subtes = 'TWK' AND tipe IS NULL;

-- Update TIU NULL berdasarkan topik
UPDATE questions SET tipe = 'numerik' WHERE subtes = 'TIU' AND tipe IS NULL AND topik IN ('Logika Matematika', 'Berhitung', 'Deret Angka', 'Perbandingan', 'Soal Cerita');
UPDATE questions SET tipe = 'verbal' WHERE subtes = 'TIU' AND tipe IS NULL AND topik IN ('Analogi', 'Silogisme', 'Analitis');
UPDATE questions SET tipe = 'figural' WHERE subtes = 'TIU' AND tipe IS NULL AND topik IN ('Ketidaksamaan', 'Serial');

-- Verifikasi hasil
SELECT subtes, tipe, COUNT(*) as total 
FROM questions 
WHERE is_active = 1 
GROUP BY subtes, tipe 
ORDER BY subtes, total DESC;
