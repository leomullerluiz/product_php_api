<?php

class AuthController extends BaseController
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function register(Request $request, array $params = []): void
    {
        $body = $this->jsonBody($request);
        $login = $this->stringInput($body, ['login', 'email', 'username']);
        $password = $this->stringInput($body, ['password', 'senha']);
        $name = $this->stringInput($body, ['name', 'nome']);
        $errors = $this->validateCredentials($login, $password);

        if ($errors !== []) {
            Response::validationError($errors);
            return;
        }

        if (UserModel::findByLogin($login) !== null) {
            Response::conflict('Ja existe um usuario cadastrado com este login.');
            return;
        }

        $user = $this->authService->register($login, $password, $name !== '' ? $name : null);

        Response::created([
            'user' => $user,
        ]);
    }

    public function login(Request $request, array $params = []): void
    {
        $body = $this->jsonBody($request);
        $login = $this->stringInput($body, ['login', 'email', 'username']);
        $password = $this->stringInput($body, ['password', 'senha']);
        $errors = $this->validateCredentials($login, $password, false);

        if ($errors !== []) {
            Response::validationError($errors);
            return;
        }

        $session = $this->authService->login($login, $password);

        if ($session === null) {
            Response::unauthorized('Login ou senha invalidos.');
            return;
        }

        Response::success($session);
    }

    public function me(Request $request, array $params = []): void
    {
        $token = $request->getAuthorizationBearerToken();

        if ($token === null) {
            Response::unauthorized('Token Bearer nao informado.');
            return;
        }

        try {
            $user = $this->authService->userFromToken($token);
        } catch (Throwable) {
            Response::unauthorized('Token invalido ou expirado.');
            return;
        }

        if ($user === null) {
            Response::unauthorized('Usuario do token nao encontrado.');
            return;
        }

        Response::success([
            'user' => $user,
        ]);
    }

    private function validateCredentials(string $login, string $password, bool $validatePasswordLength = true): array
    {
        $errors = [];

        if ($login === '') {
            $errors[] = ['field' => 'login', 'message' => 'Informe o login.'];
        }

        if (strlen($login) > 120) {
            $errors[] = ['field' => 'login', 'message' => 'O login deve ter no maximo 120 caracteres.'];
        }

        if ($password === '') {
            $errors[] = ['field' => 'password', 'message' => 'Informe a senha.'];
        }

        if ($validatePasswordLength && $password !== '' && strlen($password) < 8) {
            $errors[] = ['field' => 'password', 'message' => 'A senha deve ter pelo menos 8 caracteres.'];
        }

        return $errors;
    }
}
