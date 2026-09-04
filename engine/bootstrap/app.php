<?php

declare(strict_types=1);

use Jaroa\Application;
use Jaroa\Support\Env;

require dirname(__DIR__) . '/vendor/autoload.php';

Env::load(dirname(__DIR__) . '/.env');

return new Application();
