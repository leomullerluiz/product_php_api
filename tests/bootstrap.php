<?php

declare(strict_types=1);

$autoload = dirname(__DIR__) . '/vendor/autoload.php';

if (!file_exists($autoload)) {
    throw new RuntimeException('Execute composer install antes de rodar os testes.');
}

require_once $autoload;
