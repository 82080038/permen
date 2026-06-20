-- Migration: Add OTP columns to password_reset_requests
-- Date: 2026-06-20

ALTER TABLE password_reset_requests 
ADD COLUMN IF NOT EXISTS otp_code VARCHAR(6) NULL,
ADD COLUMN IF NOT EXISTS expires_at DATETIME NULL,
ADD COLUMN IF NOT EXISTS status VARCHAR(20) DEFAULT 'pending';

-- Index for faster OTP lookups
CREATE INDEX IF NOT EXISTS idx_password_reset_otp ON password_reset_requests(otp_code, status);
