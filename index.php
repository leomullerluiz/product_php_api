<?php
header('Content-Type: text/plain; charset=utf-8');

function loadEnv(string $path): void
{
    if (!is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value, " \t\n\r\0\x0B\"'");

        if ($name !== '' && getenv($name) === false) {
            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

loadEnv(__DIR__ . '/.env');

if (!extension_loaded('pdo_pgsql')) {
    http_response_code(500);
    echo "A extensao pdo_pgsql nao esta habilitada no PHP.";
    exit;
}

$databaseUrl = getenv('DATABASE_URL');

if ($databaseUrl === false || $databaseUrl === '') {
    http_response_code(500);
    echo "DATABASE_URL nao configurada.";
    exit;
}

$database = parse_url($databaseUrl);

if ($database === false || empty($database['host']) || empty($database['user']) || empty($database['pass']) || empty($database['path'])) {
    http_response_code(500);
    echo "DATABASE_URL invalida ou incompleta.";
    exit;
}

$host = $database['host'];
$port = $database['port'] ?? 5432;
$dbname = ltrim($database['path'], '/');
$user = urldecode($database['user']);
$password = urldecode($database['pass']);
$dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode=require";

try {
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 5,
    ]);

    $result = $pdo->query('SELECT current_database() AS database_name, current_user AS database_user')->fetch();

    echo "Conexao com Heroku Postgres realizada com sucesso!\n";
    echo "Banco: {$result['database_name']}\n";
    echo "Usuario: {$result['database_user']}\n";
} catch (PDOException $exception) {
    http_response_code(500);
    echo "Erro ao conectar no Heroku Postgres:\n";
    echo $exception->getMessage();
}
?>
