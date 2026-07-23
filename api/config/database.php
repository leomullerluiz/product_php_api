<?php

return [
    'url' => getenv('DATABASE_URL') ?: ($_ENV['DATABASE_URL'] ?? ''),
    'host' => getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? '127.0.0.1'),
    'port' => getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? '5432'),
    'database' => getenv('DB_DATABASE') ?: ($_ENV['DB_DATABASE'] ?? ($_ENV['DB_NAME'] ?? '')),
    'username' => getenv('DB_USERNAME') ?: ($_ENV['DB_USERNAME'] ?? ($_ENV['DB_USER'] ?? '')),
    'password' => getenv('DB_PASSWORD') ?: ($_ENV['DB_PASSWORD'] ?? ''),
    'sslmode' => getenv('DB_SSLMODE') ?: ($_ENV['DB_SSLMODE'] ?? 'require'),
];
