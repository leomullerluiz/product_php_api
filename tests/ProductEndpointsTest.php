<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ProductEndpointsTest extends TestCase
{
    private static string $baseUrl;
    private static ?string $token = null;

    public static function setUpBeforeClass(): void
    {
        self::$baseUrl = rtrim((string) (getenv('PRODUCT_API_BASE_URL') ?: 'http://localhost:8080'), '/');

        try {
            $response = self::request('GET', '/health');
        } catch (RuntimeException $exception) {
            self::markTestSkipped('API indisponivel em ' . self::$baseUrl . ': ' . $exception->getMessage());
        }

        if ($response['status'] !== 200) {
            self::markTestSkipped('API respondeu status inesperado em /health: ' . $response['status']);
        }
    }

    public function testProductEndpointsRequireBearerToken(): void
    {
        $response = self::request('GET', '/produtos');
        $body = self::json($response);

        self::assertSame(401, $response['status']);
        self::assertFalse($body['success']);
        self::assertSame('UNAUTHORIZED', $body['error']['code']);
    }

    public function testCreateListShowUpdatePatchAndDeleteProduct(): void
    {
        $token = self::token();
        $suffix = (string) time();
        $productId = null;

        try {
            $create = self::request('POST', '/produtos', [
                'nome' => 'Produto Teste ' . $suffix,
                'descricao' => 'Produto de teste criado pelos testes PHPUnit.',
                'preco' => 4599.90,
                'quantidade_estoque' => 12,
                'categoria' => 'Informatica',
            ], self::authHeaders($token));

            $createdBody = self::json($create);
            self::assertSame(201, $create['status']);
            self::assertTrue($createdBody['success']);
            self::assertArrayHasKey('product', $createdBody['data']);
            self::assertSame('Produto Teste ' . $suffix, $createdBody['data']['product']['nome']);
            self::assertSame(12, $createdBody['data']['product']['quantidade_estoque']);

            $productId = (int) $createdBody['data']['product']['id'];
            self::assertGreaterThan(0, $productId);

            $list = self::request('GET', '/produtos', null, self::authHeaders($token));
            $listBody = self::json($list);
            self::assertSame(200, $list['status']);
            self::assertTrue($listBody['success']);
            self::assertContains($productId, array_column($listBody['data']['products'], 'id'));

            $show = self::request('GET', "/produtos/{$productId}", null, self::authHeaders($token));
            $showBody = self::json($show);
            self::assertSame(200, $show['status']);
            self::assertSame($productId, $showBody['data']['product']['id']);

            $update = self::request('PUT', "/produtos/{$productId}", [
                'nome' => 'Update PHPUnit ' . $suffix,
                'descricao' => 'Update pelos testes PHPUnit.',
                'preco' => 4999.90,
                'quantidade_estoque' => 10,
                'categoria' => 'Informatica',
            ], self::authHeaders($token));
            $updatedBody = self::json($update);
            self::assertSame(200, $update['status']);
            self::assertSame('Update PHPUnit ' . $suffix, $updatedBody['data']['product']['nome']);
            self::assertSame(10, $updatedBody['data']['product']['quantidade_estoque']);

            $patch = self::request('PATCH', "/produtos/{$productId}", [
                'quantidade_estoque' => 8,
            ], self::authHeaders($token));
            $patchedBody = self::json($patch);
            self::assertSame(200, $patch['status']);
            self::assertSame(8, $patchedBody['data']['product']['quantidade_estoque']);

            $delete = self::request('DELETE', "/produtos/{$productId}", null, self::authHeaders($token));
            self::assertSame(204, $delete['status']);
            $productId = null;

            $deletedShow = self::request('GET', "/produtos/{$createdBody['data']['product']['id']}", null, self::authHeaders($token));
            $deletedShowBody = self::json($deletedShow);
            self::assertSame(404, $deletedShow['status']);
            self::assertSame('NOT_FOUND', $deletedShowBody['error']['code']);
        } finally {
            if ($productId !== null) {
                self::request('DELETE', "/produtos/{$productId}", null, self::authHeaders($token));
            }
        }
    }

    public function testProductValidationErrors(): void
    {
        $response = self::request('POST', '/produtos', [
            'nome' => '',
            'descricao' => '',
            'preco' => -1,
            'quantidade_estoque' => -5,
            'categoria' => '',
        ], self::authHeaders(self::token()));

        $body = self::json($response);

        self::assertSame(422, $response['status']);
        self::assertFalse($body['success']);
        self::assertSame('VALIDATION_ERROR', $body['error']['code']);
        self::assertNotEmpty($body['error']['details']);
    }

    private static function token(): string
    {
        if (self::$token !== null) {
            return self::$token;
        }

        $configuredToken = trim((string) getenv('PRODUCT_API_TEST_TOKEN'));

        if ($configuredToken !== '' && self::tokenWorks($configuredToken)) {
            self::$token = $configuredToken;
            return self::$token;
        }

        self::$token = self::createFreshToken();
        return self::$token;
    }

    private static function tokenWorks(string $token): bool
    {
        $response = self::request('GET', '/auth/me', null, self::authHeaders($token));

        return $response['status'] === 200;
    }

    private static function createFreshToken(): string
    {
        $login = 'phpunit_product_' . time() . '_' . random_int(1000, 9999);
        $password = 'admin123';

        $register = self::request('POST', '/auth/register', [
            'login' => $login,
            'senha' => $password,
            'name' => 'PHPUnit Product Tester',
        ]);

        if (!in_array($register['status'], [201, 409], true)) {
            self::fail('Nao foi possivel criar usuario de teste. Status: ' . $register['status']);
        }

        $loginResponse = self::request('POST', '/auth/login', [
            'login' => $login,
            'senha' => $password,
        ]);
        $body = self::json($loginResponse);

        self::assertSame(200, $loginResponse['status']);
        self::assertArrayHasKey('access_token', $body['data']);

        return $body['data']['access_token'];
    }

    private static function authHeaders(string $token): array
    {
        return [
            'Authorization: Bearer ' . $token,
        ];
    }

    private static function request(string $method, string $path, ?array $body = null, array $headers = []): array
    {
        $curl = curl_init(self::$baseUrl . $path);

        if ($curl === false) {
            throw new RuntimeException('Nao foi possivel inicializar cURL.');
        }

        $requestHeaders = array_merge(['Accept: application/json'], $headers);

        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_TIMEOUT => 15,
        ]);

        if ($body !== null) {
            $requestHeaders[] = 'Content-Type: application/json';
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body, JSON_THROW_ON_ERROR));
        }

        curl_setopt($curl, CURLOPT_HTTPHEADER, $requestHeaders);

        $content = curl_exec($curl);

        if ($content === false) {
            $error = curl_error($curl);
            curl_close($curl);
            throw new RuntimeException($error);
        }

        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        return [
            'status' => $status,
            'body' => $content,
        ];
    }

    private static function json(array $response): array
    {
        $decoded = json_decode($response['body'], true);

        self::assertIsArray($decoded, 'Resposta nao e JSON valido: ' . $response['body']);

        return $decoded;
    }
}
