<?php

class BaseController
{
    protected function jsonBody(Request $request): array
    {
        return $request->json();
    }

    protected function stringInput(array $data, array $keys): string
    {
        foreach ($keys as $key) {
            if (isset($data[$key]) && is_scalar($data[$key])) {
                return trim((string) $data[$key]);
            }
        }

        return '';
    }

    protected function authenticatedUser(Request $request): ?array
    {
        $token = $request->getAuthorizationBearerToken();

        if ($token === null) {
            Response::unauthorized('Token Bearer nao informado.');
            return null;
        }

        try {
            $user = (new AuthService())->userFromToken($token);
        } catch (Throwable) {
            Response::unauthorized('Token invalido ou expirado.');
            return null;
        }

        if ($user === null) {
            Response::unauthorized('Usuario do token nao encontrado.');
            return null;
        }

        return $user;
    }
}
