<?php

return [
    'opencode' => [
        'host' => env('OPENCODE_HOST', '127.0.0.1'),
        'port' => env('OPENCODE_PORT', 4096),
    ],
    'prefrontal' => [
        'url' => env('PREFRONTAL_URL', 'https://prefrontal-cortex.jordanpartridge.us/api/knowledge'),
        'token' => env('PREFRONTAL_API_TOKEN'),
    ],
    'cache' => [
        'ttl' => env('KNOW_CACHE_TTL', 3600),
        'enabled' => env('KNOW_CACHE_ENABLED', true),
    ],
];
