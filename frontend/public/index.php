<?php

require __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/../config/app.php';

$app = new App\Application($config);

$app->run();