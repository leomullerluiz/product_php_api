ALTER TABLE request_logs
  ADD COLUMN IF NOT EXISTS user_id BIGINT NULL,
  ADD COLUMN IF NOT EXISTS user_login VARCHAR(120) NULL,
  ADD COLUMN IF NOT EXISTS user_name VARCHAR(120) NULL;

CREATE INDEX IF NOT EXISTS idx_request_logs_user_id ON request_logs (user_id);

DELETE FROM request_logs
WHERE uri = '/logs'
   OR uri LIKE '/logs?%'
   OR uri = '/api/logs'
   OR uri LIKE '/api/logs?%'
   OR uri = '/api/v1/logs'
   OR uri LIKE '/api/v1/logs?%';
