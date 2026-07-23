<?php

class Database
{
    private static ?self $instance = null;
    private PDO $connection;

    private function __construct()
    {
        $config = require __DIR__ . '/../config/database.php';
        [$dsn, $username, $password] = $this->buildConnectionConfig($config);

        $this->connection = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }

    private function buildConnectionConfig(array $config): array
    {
        if (!empty($config['url'])) {
            return $this->fromDatabaseUrl($config['url'], $config['sslmode'] ?? 'require');
        }

        foreach (['host', 'port', 'database', 'username'] as $required) {
            if (empty($config[$required])) {
                throw new RuntimeException('Configuracao do banco de dados incompleta.');
            }
        }

        $sslMode = $config['sslmode'] ?: 'require';
        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s;sslmode=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $sslMode
        );

        return [$dsn, $config['username'], $config['password'] ?? ''];
    }

    private function fromDatabaseUrl(string $databaseUrl, string $defaultSslMode): array
    {
        $database = parse_url($databaseUrl);

        if (
            $database === false
            || empty($database['host'])
            || empty($database['user'])
            || empty($database['path'])
        ) {
            throw new RuntimeException('DATABASE_URL invalida ou incompleta.');
        }

        $query = [];

        if (!empty($database['query'])) {
            parse_str($database['query'], $query);
        }

        $host = $database['host'];
        $port = $database['port'] ?? 5432;
        $dbname = ltrim($database['path'], '/');
        $username = urldecode($database['user']);
        $password = urldecode($database['pass'] ?? '');
        $sslMode = $query['sslmode'] ?? $defaultSslMode;
        $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode={$sslMode}";

        return [$dsn, $username, $password];
    }
}
