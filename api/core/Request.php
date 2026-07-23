<?php

class Request
{
    private string $method;
    private string $uri;
    private array $query;
    private array $headers;
    private array $cookies;
    private ?array $jsonBody = null;

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->uri = $this->normalizeUri($_SERVER['REQUEST_URI'] ?? '/');
        $this->query = $_GET;
        $this->headers = $this->collectHeaders();
        $this->cookies = $_COOKIE;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function query(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->query;
        }

        return $this->query[$key] ?? $default;
    }

    public function cookies(): array
    {
        return $this->cookies;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function json(): array
    {
        if ($this->jsonBody !== null) {
            return $this->jsonBody;
        }

        $rawBody = file_get_contents('php://input') ?: '';

        if ($rawBody === '') {
            $this->jsonBody = [];
            return $this->jsonBody;
        }

        $decoded = json_decode($rawBody, true);
        $this->jsonBody = is_array($decoded) ? $decoded : [];

        return $this->jsonBody;
    }

    public function getAuthorizationBearerToken(): ?string
    {
        $authorization = $this->headers['Authorization']
            ?? $this->headers['authorization']
            ?? $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? null;

        if (!is_string($authorization)) {
            return null;
        }

        if (preg_match('/^Bearer\s+(.+)$/i', trim($authorization), $matches) !== 1) {
            return null;
        }

        return trim($matches[1]);
    }

    public function getOrigin(): ?string
    {
        return $this->headers['Origin'] ?? $this->headers['origin'] ?? null;
    }

    public function getClientIp(): string
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['REMOTE_ADDR']
            ?? '0.0.0.0';
    }

    private function collectHeaders(): array
    {
        if (function_exists('getallheaders')) {
            return getallheaders() ?: [];
        }

        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (!str_starts_with($key, 'HTTP_')) {
                continue;
            }

            $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
            $headers[$name] = $value;
        }

        return $headers;
    }

    private function normalizeUri(string $requestUri): string
    {
        $path = parse_url($requestUri, PHP_URL_PATH) ?: '/';
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $projectBase = rtrim(str_replace('/api', '', dirname($scriptName)), '/');

        if ($projectBase !== '' && $projectBase !== '/' && str_starts_with($path, $projectBase)) {
            $path = substr($path, strlen($projectBase)) ?: '/';
        }

        $path = preg_replace('#/(api/)?index\.php#', '', $path) ?: '/';
        $path = '/' . trim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }
}
