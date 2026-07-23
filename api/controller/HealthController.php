<?php

class HealthController extends BaseController
{
    public function health(Request $request, array $params = []): void
    {
        Response::success([
            'status' => 'online',
            'service' => 'product_php_api',
            'timestamp' => gmdate('c'),
        ]);
    }

    public function database(Request $request, array $params = []): void
    {
        $pdo = Database::getInstance()->getConnection();
        $result = $pdo->query('SELECT 1 AS ok')->fetch();

        Response::success([
            'database' => (int) $result['ok'] === 1 ? 'online' : 'unknown',
        ]);
    }

    public function sentry(Request $request, array $params = []): void
    {
        $dsn = getenv('SENTRY_DSN') ?: ($_ENV['SENTRY_DSN'] ?? '');

        if ($dsn === '') {
            Response::error('SENTRY_DSN nao configurado.', 503, 'SENTRY_NOT_CONFIGURED');
            return;
        }

        if (!function_exists('\\Sentry\\captureMessage')) {
            Response::error('SDK do Sentry nao esta carregado.', 500, 'SENTRY_SDK_NOT_LOADED');
            return;
        }

        $eventId = \Sentry\captureMessage('Health check Sentry product_php_api em ' . gmdate('c'));

        if (function_exists('\\Sentry\\flush')) {
            \Sentry\flush();
        }

        Response::success([
            'sentry' => 'ok',
            'event_id' => $eventId !== null ? (string) $eventId : null,
        ]);
    }
}
