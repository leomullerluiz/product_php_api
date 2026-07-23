<?php

class ProductService
{
    public function list(): array
    {
        return ProductModel::all();
    }

    public function find(int $id): ?array
    {
        return ProductModel::findById($id);
    }

    public function create(array $data): array
    {
        return ProductModel::create($data);
    }

    public function update(int $id, array $data): ?array
    {
        return ProductModel::update($id, $data);
    }

    public function delete(int $id): bool
    {
        return ProductModel::delete($id);
    }
}
