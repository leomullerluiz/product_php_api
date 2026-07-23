<?php

return [
    'name' => getenv('APP_NAME') ?: ($_ENV['APP_NAME'] ?? 'product_php_api'),
    'env' => getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'production'),
    'jwt_secret' => getenv('JWT_SECRET') ?: ($_ENV['JWT_SECRET'] ?? ''),
    'jwt_ttl_seconds' => (int) (getenv('JWT_TTL_SECONDS') ?: ($_ENV['JWT_TTL_SECONDS'] ?? 3600)),
];
