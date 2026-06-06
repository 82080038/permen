-- Verify jawaban_benar format consistency
-- This script checks for any jawaban_benar values that are not single characters (A, B, C, D, E)

-- Check for non-single-character values in questions table
SELECT 
    id,
    subtes,
    topik,
    jawaban_benar,
    LENGTH(jawaban_benar) as length,
    CASE 
        WHEN jawaban_benar NOT IN ('A', 'B', 'C', 'D', 'E') THEN 'INVALID'
        ELSE 'VALID'
    END as status
FROM questions
WHERE jawaban_benar IS NOT NULL 
  AND (LENGTH(jawaban_benar) != 1 OR jawaban_benar NOT IN ('A', 'B', 'C', 'D', 'E'))
ORDER BY subtes, id;

-- Count of invalid values
SELECT 
    COUNT(*) as invalid_count,
    subtes
FROM questions
WHERE jawaban_benar IS NOT NULL 
  AND (LENGTH(jawaban_benar) != 1 OR jawaban_benar NOT IN ('A', 'B', 'C', 'D', 'E'))
GROUP BY subtes;

-- Check for NULL values
SELECT COUNT(*) as null_count FROM questions WHERE jawaban_benar IS NULL;

-- Summary
SELECT 
    COUNT(*) as total_questions,
    SUM(CASE WHEN jawaban_benar IN ('A', 'B', 'C', 'D', 'E') THEN 1 ELSE 0 END) as valid_single_char,
    SUM(CASE WHEN jawaban_benar IS NOT NULL AND (LENGTH(jawaban_benar) != 1 OR jawaban_benar NOT IN ('A', 'B', 'C', 'D', 'E')) THEN 1 ELSE 0 END) as invalid_format,
    SUM(CASE WHEN jawaban_benar IS NULL THEN 1 ELSE 0 END) as null_values
FROM questions;
