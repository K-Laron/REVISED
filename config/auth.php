<?php

declare(strict_types=1);

return [
    'session_lifetime' => (int) ($_ENV['SESSION_LIFETIME'] ?? 60),
    'lockout_attempts' => 5,
    'lockout_minutes' => 15,
    'password_reset_expiry_minutes' => 15,
    'password_min_length' => 8,
];
