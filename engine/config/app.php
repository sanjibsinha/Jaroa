<?php

declare(strict_types=1);

use Jaroa\Support\Env;

return [
    'name' => Env::get('APP_NAME', 'Jaroa Engine'),
    'environment' => Env::get('APP_ENV', 'production'),
    'debug' => Env::get('APP_DEBUG', 'false') === 'true',
];
