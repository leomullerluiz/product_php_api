# product_php_api
API RESTful para gerenciamento de produtos.

## Links do projeto

- API local: `http://localhost:8080`
- API hospedada na Heroku: `https://product-php-api-d8ee8676232a.herokuapp.com`
- Swagger local: `http://localhost:8080/docs`
- Swagger na Heroku: `https://product-php-api-d8ee8676232a.herokuapp.com/docs`

## Endpoints de autenticacao

Criar usuario:

```bash
curl -X POST http://localhost:8080/auth/register \
  -H "Content-Type: application/json" \
  -d "{\"login\":\"admin\",\"senha\":\"admin123\",\"name\":\"Admin\"}"
```

Autenticar usuario:

```bash
curl -X POST http://localhost:8080/auth/login \
  -H "Content-Type: application/json" \
  -d "{\"login\":\"admin\",\"senha\":\"admin123\"}"
```

Consultar usuario autenticado:

```bash
curl http://localhost:8080/auth/me \
  -H "Authorization: SEU_TOKEN_JWT"
```

## Endpoints de produtos

Todas as rotas de produtos exigem token JWT no header `Authorization`.

Criar produto:

```bash
curl -X POST http://localhost:8080/produtos \
  -H "Content-Type: application/json" \
  -H "Authorization: SEU_TOKEN_JWT" \
  -d "{\"nome\":\"Notebook\",\"descricao\":\"Notebook corporativo\",\"preco\":4599.90,\"quantidade_estoque\":12,\"categoria\":\"Informatica\"}"
```

Listar produtos:

```bash
curl http://localhost:8080/produtos \
  -H "Authorization: SEU_TOKEN_JWT"
```

Buscar produto por ID:

```bash
curl http://localhost:8080/produtos/1 \
  -H "Authorization: SEU_TOKEN_JWT"
```

Atualizar produto:

```bash
curl -X PUT http://localhost:8080/produtos/1 \
  -H "Content-Type: application/json" \
  -H "Authorization: SEU_TOKEN_JWT" \
  -d "{\"nome\":\"Notebook Pro\",\"descricao\":\"Notebook corporativo atualizado\",\"preco\":4999.90,\"quantidade_estoque\":10,\"categoria\":\"Informatica\"}"
```

Atualizar parcialmente:

```bash
curl -X PATCH http://localhost:8080/produtos/1 \
  -H "Content-Type: application/json" \
  -H "Authorization: SEU_TOKEN_JWT" \
  -d "{\"quantidade_estoque\":8}"
```

Remover produto:

```bash
curl -X DELETE http://localhost:8080/produtos/1 \
  -H "Authorization: SEU_TOKEN_JWT"
```

## Logs de requisicoes

A rota de logs exige token JWT e paginacao obrigatoria. Os registros sao
retornados do mais recente para o mais antigo.

```bash
curl "http://localhost:8080/logs?page=0&pageSize=20" \
  -H "Authorization: SEU_TOKEN_JWT"
```

Para listar apenas logs com status diferente de `200 OK` e `204 No Content`:

```bash
curl "http://localhost:8080/logs/errors?page=0&pageSize=20" \
  -H "Authorization: SEU_TOKEN_JWT"
```

Formato de resposta:

```json
{
  "data": [
    {
      "id": 1,
      "method": "GET",
      "uri": "/produtos",
      "status_code": 200,
      "client_ip": "000.000.000.000",
      "user_agent": "PostmanRuntime/7.51.1",
      "duration_ms": 18,
      "created_at": "2026-07-25 20:10:32.123456+00",
      "user": {
        "id": 3,
        "login": "admin123",
        "name": "Administrador"
      }
    }
  ],
  "currentPage": 0,
  "pageCount": 0,
  "totalCount": 0,
  "pageSize": 20
}
```

Requisicoes para `/logs`, `/logs/errors`, `/api/logs`, `/api/logs/errors`,
`/api/v1/logs` e `/api/v1/logs/errors` nao sao gravadas na tabela de logs.

## Health checks

```text
GET /health
GET /health/database
GET /health/sentry
```

A rota `/health/sentry` envia uma mensagem de teste para o Sentry e retorna o
`event_id` gerado.

## Swagger/OpenAPI

A documentacao da API esta em `openapi.yaml` e pode ser visualizada no navegador:

```text
http://localhost:8080/docs
https://product-php-api-d8ee8676232a.herokuapp.com/docs
```

## Testes

Com a aplicacao rodando em `http://localhost:8080`, execute:

```bash
vendor/bin/phpunit
```

Para apontar para outra URL ou token:

```bash
PRODUCT_API_BASE_URL=http://localhost:8080 PRODUCT_API_TEST_TOKEN=seu_token vendor/bin/phpunit
```

Se `PRODUCT_API_TEST_TOKEN` nao for informado, os testes criam um usuario
temporario e fazem login automaticamente.

## Execucao local com Docker

Suba a aplicacao e o banco PostgreSQL:

```bash
docker compose up --build
```

A API ficara disponivel em:

```text
http://localhost:8080
```

O PostgreSQL local do Compose ficara disponivel em:

```text
localhost:5433
```

Dentro da rede Docker, a aplicacao acessa o banco pelo host `db` na porta `5432`.

O Compose executa o servico `migrate` antes de iniciar a aplicacao. Esse servico
aplica os arquivos SQL de `migrations/` no banco local.

## Variaveis obrigatorias

Para emissao de JWT, configure:

```env
JWT_SECRET=uma-chave-secreta-com-pelo-menos-32-caracteres
JWT_TTL_SECONDS=3600
SENTRY_DSN=https://seu-dsn-do-sentry
```

No GitHub Actions/Heroku, cadastre `JWT_SECRET` e `SENTRY_DSN` como secrets do
repositorio.
