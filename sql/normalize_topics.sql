-- Normalisasi nama topik untuk menghilangkan duplikasi
-- Jalankan di phpMyAdmin atau via command line

-- Update topik TKP
UPDATE questions SET topik = 'Sosial Budaya' WHERE topik = 'sosial_budaya';
UPDATE questions SET topik = 'Jejaring Kerja' WHERE topik = 'jejaring_kerja';
UPDATE questions SET topik = 'Teknologi Informasi' WHERE topik = 'teknologi_informasi';
UPDATE questions SET topik = 'Pelayanan Publik' WHERE topik = 'pelayanan_publik';

-- Verifikasi hasil
SELECT subtes, topik, COUNT(*) as total 
FROM questions 
WHERE is_active = 1 
GROUP BY subtes, topik 
ORDER BY subtes, total DESC;
