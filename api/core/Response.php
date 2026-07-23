<?php

class Response
{
    public static function json(array $payload, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function success(mixed $data = null, int $statusCode = 200): void
    {
        self::json([
            'success' => true,
            'data' => $data,
        ], $statusCode);
    }

    public static function created(mixed $data = null): void
    {
        self::success($data, 201);
    }

    public static function noContent(): void
    {
        http_response_code(204);
    }

    public static function error(
        string $message,
        int $statusCode = 400,
        string $code = 'BAD_REQUEST',
        mixed $details = null
    ): void {
        $error = [
            'code' => $code,
            'message' => $message,
        ];

        if ($details !== null) {
            $error['details'] = $details;
        }

        self::json([
            'success' => false,
            'error' => $error,
        ], $statusCode);
    }

    public static function validationError(array $details = [], string $message = 'Dados invalidos.'): void
    {
        self::error($message, 422, 'VALIDATION_ERROR', $details);
    }

    public static function unauthorized(string $message = 'Nao autenticado.'): void
    {
        self::error($message, 401, 'UNAUTHORIZED');
    }

    public static function forbidden(string $message = 'Acesso negado.'): void
    {
        self::error($message, 403, 'FORBIDDEN');
    }

    public static function notFound(string $message = 'Recurso nao encontrado.'): void
    {
        self::error($message, 404, 'NOT_FOUND');
    }

    public static function conflict(string $message = 'Conflito ao processar a requisicao.'): void
    {
        self::error($message, 409, 'CONFLICT');
    }
}
