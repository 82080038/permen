-- Cleanup old API rate limit entries
-- This should be run periodically (e.g., daily via cron job)
-- Deletes entries older than 24 hours

DELETE FROM api_rate_limits 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR);

-- Optional: Create an event for automatic daily cleanup
-- Uncomment the following lines to enable automatic cleanup:

-- DELIMITER //
-- CREATE EVENT IF NOT EXISTS cleanup_api_rate_limits
-- ON SCHEDULE EVERY 1 DAY
-- STARTS CURRENT_TIMESTAMP
-- DO
-- BEGIN
--     DELETE FROM api_rate_limits 
--     WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR);
-- END //
-- DELIMITER ;
