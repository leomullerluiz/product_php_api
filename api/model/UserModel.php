<?php

class UserModel
{
    public static function create(string $login, string $passwordHash, ?string $name = null): array
    {
        $pdo = Database::getInstance()->getConnection();
        $statement = $pdo->prepare(
            'INSERT INTO users (login, password_hash, name)
             VALUES (:login, :password_hash, :name)
             RETURNING id, login, name, created_at, updated_at'
        );

        $statement->execute([
            ':login' => $login,
            ':password_hash' => $passwordHash,
            ':name' => $name,
        ]);

        return self::formatUser($statement->fetch());
    }

    public static function findByLogin(string $login): ?array
    {
        $pdo = Database::getInstance()->getConnection();
        $statement = $pdo->prepare(
            'SELECT id, login, password_hash, name, created_at, updated_at
             FROM users
             WHERE login = :login
             LIMIT 1'
        );

        $statement->execute([':login' => $login]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public static function findById(int $id): ?array
    {
        $pdo = Database::getInstance()->getConnection();
        $statement = $pdo->prepare(
            'SELECT id, login, name, created_at, updated_at
             FROM users
             WHERE id = :id
             LIMIT 1'
        );

        $statement->execute([':id' => $id]);
        $user = $statement->fetch();

        return $user ? self::formatUser($user) : null;
    }

    public static function formatUser(array $user): array
    {
        unset($user['password_hash']);

        if (isset($user['id'])) {
            $user['id'] = (int) $user['id'];
        }

        return $user;
    }
}
