<?php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload.php';

return static function (array $context) {
    return new Kernel(
        $_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? 'prod',
        (bool) ($_SERVER['APP_DEBUG'] ?? $_ENV['APP_DEBUG'] ?? false)
    );
};