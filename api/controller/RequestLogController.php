<?php

class RequestLogController extends BaseController
{
    private const MAX_PAGE_SIZE = 100;

    private RequestLogService $requestLogService;

    public function __construct()
    {
        $this->requestLogService = new RequestLogService();
    }

    public function index(Request $request, array $params = []): void
    {
        if ($this->authenticatedUser($request) === null) {
            return;
        }

        [$page, $pageSize, $errors] = $this->paginationParams($request);

        if ($errors !== []) {
            Response::validationError($errors);
            return;
        }

        Response::json($this->requestLogService->paginate($page, $pageSize));
    }

    public function errors(Request $request, array $params = []): void
    {
        if ($this->authenticatedUser($request) === null) {
            return;
        }

        [$page, $pageSize, $errors] = $this->paginationParams($request);

        if ($errors !== []) {
            Response::validationError($errors);
            return;
        }

        Response::json($this->requestLogService->paginateErrors($page, $pageSize));
    }

    private function paginationParams(Request $request): array
    {
        $errors = [];
        $page = $this->integerQuery($request, 'page', 0, $errors);
        $pageSize = $this->integerQuery($request, 'pageSize', 1, $errors);

        if ($pageSize !== null && $pageSize > self::MAX_PAGE_SIZE) {
            $errors[] = [
                'field' => 'pageSize',
                'message' => 'O pageSize deve ser menor ou igual a ' . self::MAX_PAGE_SIZE . '.',
            ];
        }

        return [$page ?? 0, $pageSize ?? 0, $errors];
    }

    private function integerQuery(Request $request, string $field, int $minValue, array &$errors): ?int
    {
        $value = $request->query($field);

        if ($value === null || $value === '') {
            $errors[] = ['field' => $field, 'message' => 'Parametro obrigatorio.'];
            return null;
        }

        $integer = filter_var($value, FILTER_VALIDATE_INT);

        if ($integer === false || $integer < $minValue) {
            $errors[] = [
                'field' => $field,
                'message' => 'O parametro deve ser um inteiro maior ou igual a ' . $minValue . '.',
            ];
            return null;
        }

        return $integer;
    }
}
