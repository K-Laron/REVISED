<?php

declare(strict_types=1);

return [
    'host' => $_ENV['MAIL_HOST'] ?? '',
    'port' => (int) ($_ENV['MAIL_PORT'] ?? 587),
    'username' => $_ENV['MAIL_USERNAME'] ?? '',
    'password' => $_ENV['MAIL_PASSWORD'] ?? '',
    'from_address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@catarmanshelter.gov.ph',
    'from_name' => $_ENV['MAIL_FROM_NAME'] ?? 'Catarman Animal Shelter',
];
