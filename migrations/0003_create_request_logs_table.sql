CREATE TABLE IF NOT EXISTS request_logs (
  id BIGSERIAL PRIMARY KEY,
  method VARCHAR(10) NOT NULL,
  uri TEXT NOT NULL,
  status_code INTEGER NOT NULL,
  client_ip VARCHAR(255) NULL,
  user_agent TEXT NULL,
  duration_ms INTEGER NOT NULL DEFAULT 0,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_request_logs_created_at ON request_logs (created_at DESC, id DESC);
CREATE INDEX IF NOT EXISTS idx_request_logs_status_code ON request_logs (status_code);
