<?php

declare(strict_types=1);

return [
    'auth' => ['max_attempts' => 5, 'decay_minutes' => 1],
    'password_reset' => ['max_attempts' => 3, 'decay_minutes' => 1],
    'default' => ['max_attempts' => 60, 'decay_minutes' => 1],
];
