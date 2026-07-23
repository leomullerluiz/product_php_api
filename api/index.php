<?php

declare(strict_types=1);

use Dotenv\Dotenv;

$rootPath = dirname(__DIR__);
$vendorAutoload = $rootPath . '/vendor/autoload.php';

if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
}

if (class_exists(Dotenv::class)) {
    Dotenv::createImmutable($rootPath)->safeLoad();
}

spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/core/' . $class . '.php',
        __DIR__ . '/controller/' . $class . '.php',
        __DIR__ . '/model/' . $class . '.php',
        __DIR__ . '/service/' . $class . '.php',
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

$appEnv = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'production');

error_reporting(E_ALL);
ini_set('display_errors', $appEnv === 'production' ? '0' : '1');

function configureSentry(string $appEnv): void
{
    $dsn = getenv('SENTRY_DSN') ?: ($_ENV['SENTRY_DSN'] ?? '');

    if ($dsn === '' || !function_exists('\\Sentry\\init')) {
        return;
    }

    \Sentry\init([
        'dsn' => $dsn,
        'environment' => $appEnv,
    ]);
}

function configureCors(): void
{
    $policy = new CorsPolicy(getenv('CORS_ALLOWED_ORIGINS') ?: ($_ENV['CORS_ALLOWED_ORIGINS'] ?? ''));
    $policy->apply($_SERVER['HTTP_ORIGIN'] ?? null);
}

function registerRoutes(Router $router): void
{
    $route = function (string $method, string $path, string $callback) use ($router): void {
        foreach (['', '/api', '/api/v1'] as $prefix) {
            $router->{$method}($prefix . $path, $callback);
        }
    };

    $route('get', '/', 'HealthController@health');
    $route('get', '/docs', 'DocsController@swagger');
    $route('get', '/health', 'HealthController@health');
    $route('get', '/health/database', 'HealthController@database');
    $route('get', '/health/sentry', 'HealthController@sentry');

    $route('post', '/auth/register', 'AuthController@register');
    $route('post', '/auth/login', 'AuthController@login');
    $route('get', '/auth/me', 'AuthController@me');

    $route('get', '/produtos', 'ProductController@index');
    $route('post', '/produtos', 'ProductController@store');
    $route('get', '/produtos/:id', 'ProductController@show');
    $route('put', '/produtos/:id', 'ProductController@update');
    $route('patch', '/produtos/:id', 'ProductController@patch');
    $route('delete', '/produtos/:id', 'ProductController@destroy');
}

configureSentry($appEnv);

set_exception_handler(function (Throwable $exception) use ($appEnv): void {
    $details = null;

    if (function_exists('\\Sentry\\captureException')) {
        \Sentry\captureException($exception);
    }

    if ($appEnv !== 'production') {
        $details = [
            'exception' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ];
    }

    Response::error(
        $appEnv === 'production' ? 'Erro interno do servidor.' : $exception->getMessage(),
        500,
        'INTERNAL_SERVER_ERROR',
        $details
    );
});

configureCors();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    Response::noContent();
    return;
}

$request = new Request();
$router = new Router();

registerRoutes($router);
$router->dispatch($request);
