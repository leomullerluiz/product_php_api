<?php

class ProductController extends BaseController
{
    private ProductService $productService;

    public function __construct()
    {
        $this->productService = new ProductService();
    }

    public function index(Request $request, array $params = []): void
    {
        if ($this->authenticatedUser($request) === null) {
            return;
        }

        Response::success([
            'products' => $this->productService->list(),
        ]);
    }

    public function show(Request $request, array $params = []): void
    {
        if ($this->authenticatedUser($request) === null) {
            return;
        }

        $id = $this->routeId($params);

        if ($id === null) {
            Response::validationError([
                ['field' => 'id', 'message' => 'ID do produto invalido.'],
            ]);
            return;
        }

        $product = $this->productService->find($id);

        if ($product === null) {
            Response::notFound('Produto nao encontrado.');
            return;
        }

        Response::success([
            'product' => $product,
        ]);
    }

    public function store(Request $request, array $params = []): void
    {
        if ($this->authenticatedUser($request) === null) {
            return;
        }

        [$data, $errors] = $this->validatedProductData($this->jsonBody($request));

        if ($errors !== []) {
            Response::validationError($errors);
            return;
        }

        Response::created([
            'product' => $this->productService->create($data),
        ]);
    }

    public function update(Request $request, array $params = []): void
    {
        if ($this->authenticatedUser($request) === null) {
            return;
        }

        $id = $this->routeId($params);

        if ($id === null) {
            Response::validationError([
                ['field' => 'id', 'message' => 'ID do produto invalido.'],
            ]);
            return;
        }

        [$data, $errors] = $this->validatedProductData($this->jsonBody($request));

        if ($errors !== []) {
            Response::validationError($errors);
            return;
        }

        $product = $this->productService->update($id, $data);

        if ($product === null) {
            Response::notFound('Produto nao encontrado.');
            return;
        }

        Response::success([
            'product' => $product,
        ]);
    }

    public function patch(Request $request, array $params = []): void
    {
        if ($this->authenticatedUser($request) === null) {
            return;
        }

        $id = $this->routeId($params);

        if ($id === null) {
            Response::validationError([
                ['field' => 'id', 'message' => 'ID do produto invalido.'],
            ]);
            return;
        }

        [$data, $errors] = $this->validatedProductData($this->jsonBody($request), true);

        if ($errors !== []) {
            Response::validationError($errors);
            return;
        }

        $product = $this->productService->update($id, $data);

        if ($product === null) {
            Response::notFound('Produto nao encontrado.');
            return;
        }

        Response::success([
            'product' => $product,
        ]);
    }

    public function destroy(Request $request, array $params = []): void
    {
        if ($this->authenticatedUser($request) === null) {
            return;
        }

        $id = $this->routeId($params);

        if ($id === null) {
            Response::validationError([
                ['field' => 'id', 'message' => 'ID do produto invalido.'],
            ]);
            return;
        }

        if (!$this->productService->delete($id)) {
            Response::notFound('Produto nao encontrado.');
            return;
        }

        Response::noContent();
    }

    private function routeId(array $params): ?int
    {
        $id = $params['id'] ?? null;

        if ($id === null || filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            return null;
        }

        return (int) $id;
    }

    private function validatedProductData(array $body, bool $partial = false): array
    {
        $data = [];
        $errors = [];
        $fields = ['nome', 'descricao', 'preco', 'quantidade_estoque', 'categoria'];
        $receivedFields = array_values(array_intersect($fields, array_keys($body)));

        if ($partial && $receivedFields === []) {
            $errors[] = ['field' => 'body', 'message' => 'Informe ao menos um campo para atualizar.'];
            return [$data, $errors];
        }

        foreach (['nome', 'descricao', 'categoria'] as $field) {
            if (!array_key_exists($field, $body)) {
                if (!$partial) {
                    $errors[] = ['field' => $field, 'message' => 'Campo obrigatorio.'];
                }

                continue;
            }

            if (!is_scalar($body[$field])) {
                $errors[] = ['field' => $field, 'message' => 'O campo deve ser um texto.'];
                continue;
            }

            $value = trim((string) $body[$field]);

            if ($value === '') {
                $errors[] = ['field' => $field, 'message' => 'Campo obrigatorio.'];
                continue;
            }

            if (strlen($value) > 120 && in_array($field, ['nome', 'categoria'], true)) {
                $errors[] = ['field' => $field, 'message' => 'O campo deve ter no maximo 120 caracteres.'];
                continue;
            }

            $data[$field] = $value;
        }

        if (!array_key_exists('preco', $body)) {
            if (!$partial) {
                $errors[] = ['field' => 'preco', 'message' => 'Campo obrigatorio.'];
            }
        } elseif (!is_numeric($body['preco']) || (float) $body['preco'] < 0) {
            $errors[] = ['field' => 'preco', 'message' => 'O preco deve ser um numero maior ou igual a zero.'];
        } else {
            $data['preco'] = round((float) $body['preco'], 2);
        }

        if (!array_key_exists('quantidade_estoque', $body)) {
            if (!$partial) {
                $errors[] = ['field' => 'quantidade_estoque', 'message' => 'Campo obrigatorio.'];
            }
        } else {
            $stock = filter_var($body['quantidade_estoque'], FILTER_VALIDATE_INT);

            if ($stock === false || $stock < 0) {
                $errors[] = ['field' => 'quantidade_estoque', 'message' => 'A quantidade em estoque deve ser um inteiro maior ou igual a zero.'];
            } else {
                $data['quantidade_estoque'] = $stock;
            }
        }

        return [$data, $errors];
    }
}
