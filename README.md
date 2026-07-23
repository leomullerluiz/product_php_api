# product_php_api
Api for product management 

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
  -H "Authorization: Bearer SEU_TOKEN_JWT"
```

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
```

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
