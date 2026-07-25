<?php

class RequestLogModel
{
    public static function create(array $data): void
    {
        $pdo = Database::getInstance()->getConnection();
        $statement = $pdo->prepare(
            'INSERT INTO request_logs (
                method,
                uri,
                status_code,
                client_ip,
                user_agent,
                duration_ms,
                user_id,
                user_login,
                user_name
             )
             VALUES (
                :method,
                :uri,
                :status_code,
                :client_ip,
                :user_agent,
                :duration_ms,
                :user_id,
                :user_login,
                :user_name
             )'
        );

        $statement->execute([
            ':method' => $data['method'],
            ':uri' => $data['uri'],
            ':status_code' => $data['status_code'],
            ':client_ip' => $data['client_ip'],
            ':user_agent' => $data['user_agent'],
            ':duration_ms' => $data['duration_ms'],
            ':user_id' => $data['user_id'],
            ':user_login' => $data['user_login'],
            ':user_name' => $data['user_name'],
        ]);
    }

    public static function paginate(int $page, int $pageSize): array
    {
        return self::paginateWhere($page, $pageSize);
    }

    public static function paginateErrors(int $page, int $pageSize): array
    {
        return self::paginateWhere($page, $pageSize, 'WHERE status_code NOT IN (200, 204)');
    }

    private static function paginateWhere(int $page, int $pageSize, string $whereClause = ''): array
    {
        $pdo = Database::getInstance()->getConnection();
        $totalCount = (int) $pdo->query('SELECT COUNT(*) FROM request_logs ' . $whereClause)->fetchColumn();
        $pageCount = $totalCount === 0 ? 0 : (int) ceil($totalCount / $pageSize);
        $offset = $page * $pageSize;

        $statement = $pdo->prepare(
            'SELECT id,
                    method,
                    uri,
                    status_code,
                    client_ip,
                    user_agent,
                    duration_ms,
                    user_id,
                    user_login,
                    user_name,
                    created_at
             FROM request_logs
             ' . $whereClause . '
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
        $log['user'] = self::formatUser($log);

        unset($log['user_id'], $log['user_login'], $log['user_name']);

        return $log;
    }

    private static function formatUser(array $log): ?array
    {
        if ($log['user_id'] === null && $log['user_login'] === null) {
            return null;
        }

        return [
            'id' => $log['user_id'] === null ? null : (int) $log['user_id'],
            'login' => $log['user_login'],
            'name' => $log['user_name'],
        ];
    }
}
