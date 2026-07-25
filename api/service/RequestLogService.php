<?php

class RequestLogService
{
    public function record(Request $request, int $statusCode, float $startedAt): void
    {
        try {
            RequestLogModel::create([
                'method' => $request->method(),
                'uri' => $this->requestUri(),
                'status_code' => $statusCode,
                'client_ip' => $request->getClientIp(),
                'user_agent' => $this->header($request, 'User-Agent'),
                'duration_ms' => $this->durationMs($startedAt),
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
