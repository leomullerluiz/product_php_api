<?php

class ProductModel
{
    public static function all(): array
    {
        $pdo = Database::getInstance()->getConnection();
        $statement = $pdo->query(
            'SELECT id, nome, descricao, preco, quantidade_estoque, categoria, created_at, updated_at
             FROM products
             ORDER BY id ASC'
        );

        return array_map([self::class, 'formatProduct'], $statement->fetchAll());
    }

    public static function findById(int $id): ?array
    {
        $pdo = Database::getInstance()->getConnection();
        $statement = $pdo->prepare(
            'SELECT id, nome, descricao, preco, quantidade_estoque, categoria, created_at, updated_at
             FROM products
             WHERE id = :id
             LIMIT 1'
        );

        $statement->execute([':id' => $id]);
        $product = $statement->fetch();

        return $product ? self::formatProduct($product) : null;
    }

    public static function create(array $data): array
    {
        $pdo = Database::getInstance()->getConnection();
        $statement = $pdo->prepare(
            'INSERT INTO products (nome, descricao, preco, quantidade_estoque, categoria)
             VALUES (:nome, :descricao, :preco, :quantidade_estoque, :categoria)
             RETURNING id, nome, descricao, preco, quantidade_estoque, categoria, created_at, updated_at'
        );

        $statement->execute([
            ':nome' => $data['nome'],
            ':descricao' => $data['descricao'],
            ':preco' => $data['preco'],
            ':quantidade_estoque' => $data['quantidade_estoque'],
            ':categoria' => $data['categoria'],
        ]);

        return self::formatProduct($statement->fetch());
    }

    public static function update(int $id, array $data): ?array
    {
        $allowedFields = ['nome', 'descricao', 'preco', 'quantidade_estoque', 'categoria'];
        $sets = [];
        $params = [':id' => $id];

        foreach ($allowedFields as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            $sets[] = "{$field} = :{$field}";
            $params[":{$field}"] = $data[$field];
        }

        if ($sets === []) {
            return self::findById($id);
        }

        $sets[] = 'updated_at = NOW()';
        $sql = sprintf(
            'UPDATE products
             SET %s
             WHERE id = :id
             RETURNING id, nome, descricao, preco, quantidade_estoque, categoria, created_at, updated_at',
            implode(', ', $sets)
        );

        $pdo = Database::getInstance()->getConnection();
        $statement = $pdo->prepare($sql);
        $statement->execute($params);
        $product = $statement->fetch();

        return $product ? self::formatProduct($product) : null;
    }

    public static function delete(int $id): bool
    {
        $pdo = Database::getInstance()->getConnection();
        $statement = $pdo->prepare('DELETE FROM products WHERE id = :id');
        $statement->execute([':id' => $id]);

        return $statement->rowCount() > 0;
    }

    private static function formatProduct(array $product): array
    {
        $product['id'] = (int) $product['id'];
        $product['preco'] = (float) $product['preco'];
        $product['quantidade_estoque'] = (int) $product['quantidade_estoque'];

        return $product;
    }
}
