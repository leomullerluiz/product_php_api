<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthService
{
    private array $config;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../config/app.php';
    }

    public function register(string $login, string $password, ?string $name = null): array
    {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        return UserModel::create($login, $passwordHash, $name);
    }

    public function login(string $login, string $password): ?array
    {
        $user = UserModel::findByLogin($login);

        if ($user === null || !password_verify($password, $user['password_hash'])) {
            return null;
        }

        $safeUser = UserModel::formatUser($user);
        $session = $this->createSession($safeUser);

        return [
            'token_type' => 'JWT',
            'access_token' => $session['token'],
            'expires_in' => $this->ttl(),
            'expires_at' => $session['expires_at'],
            'user' => $safeUser,
        ];
    }

    public function userFromToken(string $token): ?array
    {
        $accessToken = AccessTokenModel::findValid($token);

        if ($accessToken === null) {
            return null;
        }

        $payload = $this->decodeToken($token);
        $userId = isset($payload['sub']) ? (int) $payload['sub'] : 0;

        if ($userId <= 0 || $userId !== (int) $accessToken['user_id']) {
            return null;
        }

        return UserModel::findById($userId);
    }

    private function createSession(array $user): array
    {
        $expiresAt = time() + $this->ttl();
        $token = $this->createToken($user, $expiresAt);
        $expiresAtIso = gmdate(DATE_ATOM, $expiresAt);

        AccessTokenModel::create((int) $user['id'], $token, $expiresAtIso);

        return [
            'token' => $token,
            'expires_at' => $expiresAtIso,
        ];
    }

    private function createToken(array $user, int $expiresAt): string
    {
        $now = time();
        $payload = [
            'iss' => $this->config['name'],
            'iat' => $now,
            'nbf' => $now,
            'exp' => $expiresAt,
            'sub' => (string) $user['id'],
            'login' => $user['login'],
            'name' => $user['name'] ?? null,
        ];

        return JWT::encode($payload, $this->secret(), 'HS256');
    }

    private function decodeToken(string $token): array
    {
        $decoded = JWT::decode($token, new Key($this->secret(), 'HS256'));

        return json_decode(json_encode($decoded, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
    }

    private function secret(): string
    {
        $secret = (string) ($this->config['jwt_secret'] ?? '');

        if ($secret === '' || strlen($secret) < 32) {
            throw new RuntimeException('JWT_SECRET deve estar configurado com pelo menos 32 caracteres.');
        }

        return $secret;
    }

    private function ttl(): int
    {
        $ttl = (int) ($this->config['jwt_ttl_seconds'] ?? 3600);

        return $ttl > 0 ? $ttl : 3600;
    }
}
