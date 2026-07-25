<?php

class RequestLogModel
{
    public static function create(array $data): void
    {
        $pdo = Database::getInstance()->getConnection();
        $statement = $pdo->prepare(
            'INSERT INTO request_logs (method, uri, status_code, client_ip, user_agent, duration_ms)
             VALUES (:method, :uri, :status_code, :client_ip, :user_agent, :duration_ms)'
        );

        $statement->execute([
            ':method' => $data['method'],
            ':uri' => $data['uri'],
            ':status_code' => $data['status_code'],
            ':client_ip' => $data['client_ip'],
            ':user_agent' => $data['user_agent'],
            ':duration_ms' => $data['duration_ms'],
        ]);
    }

    public static function paginate(int $page, int $pageSize): array
    {
        $pdo = Database::getInstance()->getConnection();
        $totalCount = (int) $pdo->query('SELECT COUNT(*) FROM request_logs')->fetchColumn();
        $pageCount = $totalCount === 0 ? 0 : (int) ceil($totalCount / $pageSize);
        $offset = $page * $pageSize;

        $statement = $pdo->prepare(
            'SELECT id, method, uri, status_code, client_ip, user_agent, duration_ms, created_at
             FROM request_logs
             ORDER BY created_at DESC, id DESC
             LIMIT :limit OFFSET :offset'
        );

        $statement->bindValue(':limit', $pageSize, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return [
            'data' => array_map([self::class, 'formatLog'], $statement->fetchAll()),
            'currentPage' => $page,
            'pageCount' => $pageCount,
            'totalCount' => $totalCount,
            'pageSize' => $pageSize,
        ];
    }

    private static function formatLog(array $log): array
    {
        $log['id'] = (int) $log['id'];
        $log['status_code'] = (int) $log['status_code'];
        $log['duration_ms'] = (int) $log['duration_ms'];

        return $log;
    }
}
