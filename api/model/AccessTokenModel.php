<?php

class AccessTokenModel
{
    public static function create(int $userId, string $token, string $expiresAt): array
    {
        $pdo = Database::getInstance()->getConnection();
        $statement = $pdo->prepare(
            'INSERT INTO access_tokens (user_id, token, expires_at)
             VALUES (:user_id, :token, :expires_at)
             RETURNING id, user_id, token, expires_at, created_at'
        );

        $statement->execute([
            ':user_id' => $userId,
            ':token' => $token,
            ':expires_at' => $expiresAt,
        ]);

        return self::formatToken($statement->fetch());
    }

    public static function findValid(string $token): ?array
    {
        $pdo = Database::getInstance()->getConnection();
        $statement = $pdo->prepare(
            'SELECT id, user_id, token, expires_at, created_at
             FROM access_tokens
             WHERE token = :token
               AND expires_at > NOW()
             LIMIT 1'
        );

        $statement->execute([':token' => $token]);
        $accessToken = $statement->fetch();

        return $accessToken ? self::formatToken($accessToken) : null;
    }

    private static function formatToken(array $accessToken): array
    {
        if (isset($accessToken['id'])) {
            $accessToken['id'] = (int) $accessToken['id'];
        }

        if (isset($accessToken['user_id'])) {
            $accessToken['user_id'] = (int) $accessToken['user_id'];
        }

        return $accessToken;
    }
}
