-- Reset passing grade instansi ke standar resmi BKN 2024
-- Semua instansi menggunakan passing grade yang sama sesuai ketentuan BKN

UPDATE instansi SET 
    passing_tkp = 156,
    passing_tiu = 80,
    passing_twk = 65,
    passing_total = 301
WHERE aktif = 1;
