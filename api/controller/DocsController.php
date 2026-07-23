<?php

class DocsController extends BaseController
{
    public function swagger(Request $request, array $params = []): void
    {
        header('Content-Type: text/html; charset=utf-8');

        echo <<<'HTML'
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Product PHP API - Swagger</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css">
    <style>
        body {
            margin: 0;
            background: #ffffff;
        }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
    <script>
        window.addEventListener('load', function () {
            SwaggerUIBundle({
                url: '/openapi.yaml',
                dom_id: '#swagger-ui',
                deepLinking: true,
                displayRequestDuration: true,
                persistAuthorization: true
            });
        });
    </script>
</body>
</html>
HTML;
    }
}
