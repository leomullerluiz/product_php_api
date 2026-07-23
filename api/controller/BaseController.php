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
}
