<?php
require_once __DIR__ . '/../config.php';

echo "=== Database Storage Analysis ===\n\n";

// Analyze current storage
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_questions,
        AVG(LENGTH(pertanyaan)) as avg_question_len,
        AVG(LENGTH(pilihan_a)) as avg_option_len,
        AVG(LENGTH(pilihan_b)) as avg_option_len,
        AVG(LENGTH(pilihan_c)) as avg_option_len,
        AVG(LENGTH(pilihan_d)) as avg_option_len,
        AVG(LENGTH(pilihan_e)) as avg_option_len,
        AVG(LENGTH(jawaban_benar)) as avg_correct_len,
        AVG(LENGTH(pembahasan)) as avg_explanation_len
    FROM questions
");

$stats = $stmt->fetch();

echo "Current Storage Statistics:\n";
echo "Total Questions: {$stats['total_questions']}\n";
echo "Average Question Length: {$stats['avg_question_len']} chars\n";
echo "Average Option Length: {$stats['avg_option_len']} chars\n";
echo "Average Correct Answer Length: {$stats['avg_correct_len']} chars\n";
echo "Average Explanation Length: {$stats['avg_explanation_len']} chars\n\n";

// Calculate estimated storage per question
$avgTotalLen = $stats['avg_question_len'] + 
               ($stats['avg_option_len'] * 5) + 
               $stats['avg_correct_len'] + 
               $stats['avg_explanation_len'];

echo "Estimated storage per question: ~" . number_format($avgTotalLen) . " bytes\n";
echo "Estimated total storage: ~" . number_format($avgTotalLen * $stats['total_questions']) . " bytes (" . number_format(($avgTotalLen * $stats['total_questions']) / 1024 / 1024, 2) . " MB)\n\n";

// Compare with char(1) approach
$char1Storage = $stats['avg_question_len'] + 
                ($stats['avg_option_len'] * 5) + 
                1 + // char(1) for jawaban_benar
                $stats['avg_explanation_len'];

echo "If jawaban_benar was char(1):\n";
echo "Estimated storage per question: ~" . number_format($char1Storage) . " bytes\n";
echo "Estimated total storage: ~" . number_format($char1Storage * $stats['total_questions']) . " bytes (" . number_format(($char1Storage * $stats['total_questions']) / 1024 / 1024, 2) . " MB)\n\n";

$difference = ($avgTotalLen - $char1Storage) * $stats['total_questions'];
echo "Storage difference: ~" . number_format($difference) . " bytes (" . number_format($difference / 1024 / 1024, 2) . " MB)\n\n";

// Analyze user answers storage
$stmt = $pdo->query("SELECT COUNT(*) as total_answers, AVG(LENGTH(jawaban)) as avg_user_answer FROM answers");
$answerStats = $stmt->fetch();

echo "User Answers Storage:\n";
echo "Total Answers: {$answerStats['total_answers']}\n";
echo "Average User Answer Length: {$answerStats['avg_user_answer']} chars\n";
echo "Estimated storage: ~" . number_format($answerStats['avg_user_answer'] * $answerStats['total_answers']) . " bytes (" . number_format(($answerStats['avg_user_answer'] * $answerStats['total_answers']) / 1024, 2) . " KB)\n\n";

echo "=== Analysis Complete ===\n";
