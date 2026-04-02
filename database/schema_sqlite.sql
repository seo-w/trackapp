-- TrackApp: esquema base (SQLite)
-- Ejecutar con: php database/migrate.php o al usar PDO por primera vez

CREATE TABLE IF NOT EXISTS app_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    api_base_url VARCHAR(512) NOT NULL DEFAULT '',
    tienda_id VARCHAR(191) NOT NULL DEFAULT '',
    access_token_encrypted TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS query_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    requested_states VARCHAR(512) NOT NULL DEFAULT '',
    result_count INTEGER NOT NULL DEFAULT 0,
    success BOOLEAN NOT NULL DEFAULT 0,
    error_message TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_query_logs_created_at ON query_logs (created_at);

CREATE TABLE IF NOT EXISTS products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    remote_id INTEGER UNIQUE NOT NULL,
    name VARCHAR(255) NULL,
    description TEXT NULL,
    warehouse VARCHAR(255) NULL,
    image_url TEXT NULL,
    raw_json TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_products_remote_id ON products (remote_id);

