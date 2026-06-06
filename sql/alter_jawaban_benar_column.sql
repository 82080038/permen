-- Alter jawaban_benar column from char(1) to varchar(255)
-- This allows storing actual answer text instead of just letter labels

ALTER TABLE questions MODIFY COLUMN jawaban_benar VARCHAR(255);
