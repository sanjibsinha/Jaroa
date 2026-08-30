<?php

return [
    'name' => 'Jaroa',

    'environment' => getenv('JAROA_ENV') ?: 'local',

    'api' => [
        'base_url' => getenv('JAROA_API_URL')
            ?: 'https://backend.ddev.site/wp-json/fullstack/v1',
    ],
];
