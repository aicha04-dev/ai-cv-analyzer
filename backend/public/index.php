<?php

use App\Kernel;

if (!isset($_SERVER['APP_ENV'])) {
    $_SERVER['APP_ENV'] = 'prod';
}

if (!isset($_SERVER['APP_DEBUG'])) {
    $_SERVER['APP_DEBUG'] = '0';
}

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return static function (array $context) {
    return new Kernel(
        $context['APP_ENV'],
        (bool) $context['APP_DEBUG']
    );
};