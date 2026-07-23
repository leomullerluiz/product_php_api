<?php

class Router
{
    private array $routes = [];

    public function get(string $path, callable|string $callback): void
    {
        $this->add('GET', $path, $callback);
    }

    public function post(string $path, callable|string $callback): void
    {
        $this->add('POST', $path, $callback);
    }

    public function put(string $path, callable|string $callback): void
    {
        $this->add('PUT', $path, $callback);
    }

    public function patch(string $path, callable|string $callback): void
    {
        $this->add('PATCH', $path, $callback);
    }

    public function delete(string $path, callable|string $callback): void
    {
        $this->add('DELETE', $path, $callback);
    }

    public function dispatch(Request $request): void
    {
        foreach ($this->routes[$request->method()] ?? [] as $route) {
            $params = $this->match($route['path'], $request->uri());

            if ($params === null) {
                continue;
            }

            $this->execute($route['callback'], $request, $params);
            return;
        }

        Response::notFound();
    }

    private function add(string $method, string $path, callable|string $callback): void
    {
        $this->routes[$method][] = [
            'path' => $this->normalizePath($path),
            'callback' => $callback,
        ];
    }

    private function execute(callable|string $callback, Request $request, array $params): void
    {
        if (is_callable($callback)) {
            $callback($request, $params);
            return;
        }

        [$controllerName, $methodName] = explode('@', $callback, 2);

        if (!class_exists($controllerName) || !method_exists($controllerName, $methodName)) {
            throw new RuntimeException("Rota invalida: {$callback}");
        }

        $controller = new $controllerName();
        $controller->{$methodName}($request, $params);
    }

    private function match(string $routePath, string $requestPath): ?array
    {
        if ($routePath === $requestPath) {
            return [];
        }

        $routeParts = $this->pathParts($routePath);
        $requestParts = $this->pathParts($requestPath);

        if (count($routeParts) !== count($requestParts)) {
            return null;
        }

        $params = [];

        foreach ($routeParts as $index => $routePart) {
            $requestPart = $requestParts[$index];

            if (str_starts_with($routePart, ':')) {
                $params[substr($routePart, 1)] = urldecode($requestPart);
                continue;
            }

            if ($routePart !== $requestPart) {
                return null;
            }
        }

        return $params;
    }

    private function pathParts(string $path): array
    {
        if ($path === '/') {
            return [];
        }

        return explode('/', trim($path, '/'));
    }

    private function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '/' ? '/' : rtrim($path, '/');
    }
}
