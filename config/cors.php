<?php

declare(strict_types=1);

return [
    'allowed_origins' => [
        $_ENV['APP_URL'] ?? 'http://localhost:8000',
    ],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With', 'X-CSRF-TOKEN'],
    'supports_credentials' => true,
];
