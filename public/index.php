<?php

use App\Kernel;

if (!file_exists(dirname(__DIR__).'/.env')) {
    $_SERVER['APP_RUNTIME_OPTIONS'] ??= [];
    $_SERVER['APP_RUNTIME_OPTIONS']['disable_dotenv'] = true;
}

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
