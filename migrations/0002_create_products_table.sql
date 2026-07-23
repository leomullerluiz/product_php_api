CREATE TABLE IF NOT EXISTS products (
  id BIGSERIAL PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  descricao TEXT NOT NULL,
  preco NUMERIC(10, 2) NOT NULL CHECK (preco >= 0),
  quantidade_estoque INTEGER NOT NULL CHECK (quantidade_estoque >= 0),
  categoria VARCHAR(120) NOT NULL,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NULL
);

CREATE INDEX IF NOT EXISTS idx_products_categoria ON products (categoria);
