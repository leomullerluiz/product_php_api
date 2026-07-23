# product_php_api
Api for product management 

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
