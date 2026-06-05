-- Update passing grade instansi dengan nilai yang lebih realistis dan berbeda-beda
-- Berdasarkan standar umum masing-masing instansi kedinasan

UPDATE instansi SET 
    passing_tkp = 166,
    passing_tiu = 80,
    passing_twk = 75,
    passing_total = 321
WHERE kode = 'STAN';

UPDATE instansi SET 
    passing_tkp = 156,
    passing_tiu = 85,
    passing_twk = 70,
    passing_total = 311
WHERE kode = 'STIS';

UPDATE instansi SET 
    passing_tkp = 143,
    passing_tiu = 80,
    passing_twk = 65,
    passing_total = 288
WHERE kode = 'IPDN';

UPDATE instansi SET 
    passing_tkp = 150,
    passing_tiu = 80,
    passing_twk = 65,
    passing_total = 295
WHERE kode = 'STMKG';

UPDATE instansi SET 
    passing_tkp = 154,
    passing_tiu = 90,
    passing_twk = 70,
    passing_total = 314
WHERE kode = 'POLTEK_SSN';

UPDATE instansi SET 
    passing_tkp = 143,
    passing_tiu = 80,
    passing_twk = 65,
    passing_total = 288
WHERE kode = 'PTIK';

UPDATE instansi SET 
    passing_tkp = 150,
    passing_tiu = 80,
    passing_twk = 65,
    passing_total = 295
WHERE kode = 'POLTEK_IMIGRASI';

UPDATE instansi SET 
    passing_tkp = 154,
    passing_tiu = 85,
    passing_twk = 70,
    passing_total = 309
WHERE kode = 'STIN';

UPDATE instansi SET 
    passing_tkp = 154,
    passing_tiu = 85,
    passing_twk = 70,
    passing_total = 309
WHERE kode = 'AKPOL';

UPDATE instansi SET 
    passing_tkp = 154,
    passing_tiu = 85,
    passing_twk = 70,
    passing_total = 309
WHERE kode = 'AKMIL';

UPDATE instansi SET 
    passing_tkp = 154,
    passing_tiu = 85,
    passing_twk = 70,
    passing_total = 309
WHERE kode = 'AAU';

UPDATE instansi SET 
    passing_tkp = 154,
    passing_tiu = 85,
    passing_twk = 70,
    passing_total = 309
WHERE kode = 'AAL';
