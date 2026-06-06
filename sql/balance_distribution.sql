-- Balance distribution by deleting excess questions from dominant topics
-- Target: ~50 per topic for TKP/TWK, ~100 per topic for TIU

-- TKP: Reduce Kepribadian from 251 to 50 (delete 201)
DELETE FROM questions WHERE subtes = 'TKP' AND topik = 'Kepribadian' AND is_active = 1 ORDER BY id LIMIT 201;

-- TWK: Reduce Nasionalisme from 629 to 50 (delete 579)
DELETE FROM questions WHERE subtes = 'TWK' AND topik = 'Nasionalisme' AND is_active = 1 ORDER BY id LIMIT 579;

-- TIU: Reduce Logika Matematika from 800 to 100 (delete 700)
DELETE FROM questions WHERE subtes = 'TIU' AND topik = 'Logika Matematika' AND is_active = 1 ORDER BY id LIMIT 700;

-- TIU: Reduce Berhitung from 292 to 100 (delete 192)
DELETE FROM questions WHERE subtes = 'TIU' AND topik = 'Berhitung' AND is_active = 1 ORDER BY id LIMIT 192;

-- TIU: Reduce Deret Angka from 209 to 100 (delete 109)
DELETE FROM questions WHERE subtes = 'TIU' AND topik = 'Deret Angka' AND is_active = 1 ORDER BY id LIMIT 109;

-- Verify final distribution
SELECT subtes, topik, COUNT(*) as total 
FROM questions 
WHERE is_active = 1 
GROUP BY subtes, topik 
ORDER BY subtes, total DESC;
