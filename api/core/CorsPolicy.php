<?php

class CorsPolicy
{
    private array $allowedOrigins;

    public function __construct(string $allowedOrigins)
    {
        $this->allowedOrigins = array_values(array_filter(array_map('trim', explode(',', $allowedOrigins))));
    }

    public function apply(?string $origin): void
    {
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token');
        header('Access-Control-Max-Age: 86400');

        if ($origin === null || !$this->isAllowed($origin)) {
            return;
        }

        header("Access-Control-Allow-Origin: {$origin}");
        header('Access-Control-Allow-Credentials: true');
        header('Vary: Origin');
    }

    private function isAllowed(string $origin): bool
    {
        return in_array($origin, $this->allowedOrigins, true);
    }
}
