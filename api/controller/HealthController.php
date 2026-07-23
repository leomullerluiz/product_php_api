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
}
