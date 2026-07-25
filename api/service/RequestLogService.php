<?php

class RequestLogService
{
    public function record(Request $request, int $statusCode, float $startedAt): void
    {
        if ($this->shouldSkip($request)) {
            return;
        }

        try {
            $user = $this->userFromRequest($request);

            RequestLogModel::create([
                'method' => $request->method(),
                'uri' => $this->requestUri(),
                'status_code' => $statusCode,
                'client_ip' => $request->getClientIp(),
                'user_agent' => $this->header($request, 'User-Agent'),
                'duration_ms' => $this->durationMs($startedAt),
                'user_id' => $user['id'] ?? null,
                'user_login' => $user['login'] ?? null,
                'user_name' => $user['name'] ?? null,
            ]);
        } catch (Throwable $exception) {
            if (function_exists('\\Sentry\\captureException')) {
                \Sentry\captureException($exception);
            }
        }
    }

    public function paginate(int $page, int $pageSize): array
    {
        return RequestLogModel::paginate($page, $pageSize);
    }

    public function paginateErrors(int $page, int $pageSize): array
    {
        return RequestLogModel::paginateErrors($page, $pageSize);
    }

    private function shouldSkip(Request $request): bool
    {
        return in_array($request->uri(), [
            '/logs',
            '/api/logs',
            '/api/v1/logs',
            '/logs/errors',
            '/api/logs/errors',
            '/api/v1/logs/errors',
        ], true);
    }

    private function userFromRequest(Request $request): ?array
    {
        $token = $request->getAuthorizationJwtToken();

        if ($token === null) {
            return null;
        }

        try {
            return (new AuthService())->userFromToken($token);
        } catch (Throwable) {
            return null;
        }
    }

    private function requestUri(): string
    {
        return (string) ($_SERVER['REQUEST_URI'] ?? '/');
    }

    private function header(Request $request, string $name): ?string
    {
        $headers = $request->headers();
        $lowerName = strtolower($name);

        foreach ($headers as $headerName => $value) {
            if (strtolower((string) $headerName) === $lowerName && is_scalar($value)) {
                return (string) $value;
            }
        }

        return null;
    }

    private function durationMs(float $startedAt): int
    {
        return max(0, (int) round((microtime(true) - $startedAt) * 1000));
    }
}
