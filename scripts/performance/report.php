<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

if (file_exists(dirname(__DIR__, 2) . '/.env')) {
    \Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2))->safeLoad();
}

require dirname(__DIR__, 2) . '/config/app.php';

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Session;
use App\Models\User;
use App\Support\Performance\PerformanceReportFormatter;

$_ENV['APP_PERFORMANCE_DEBUG'] = '1';
$heading = $argv[1] ?? 'Before Optimization';

$cases = [
    ['label' => 'dashboard_stats', 'method' => 'GET', 'uri' => '/api/dashboard/stats', 'query' => []],
    ['label' => 'dashboard_activity', 'method' => 'GET', 'uri' => '/api/dashboard/activity', 'query' => []],
    ['label' => 'search_animals', 'method' => 'GET', 'uri' => '/api/search/global', 'query' => ['q' => 'Buddy', 'modules' => ['animals'], 'per_section' => 5]],
    ['label' => 'search_all', 'method' => 'GET', 'uri' => '/api/search/global', 'query' => ['q' => 'Catarman', 'per_section' => 5]],
];

if (session_status() !== PHP_SESSION_ACTIVE) {
    Session::start();
}

$userRow = Database::fetch(
    'SELECT u.*, r.name AS role_name, r.display_name AS role_display_name
     FROM users u
     INNER JOIN roles r ON r.id = u.role_id
     WHERE r.name = :role_name AND u.is_deleted = 0
     ORDER BY u.id ASC
     LIMIT 1',
    ['role_name' => 'super_admin']
);

if ($userRow === false) {
    fwrite(STDERR, "No active super_admin user found.\n");
    exit(1);
}

$users = new User();
$userRow['permissions'] = $users->permissions((int) $userRow['id']);
unset($userRow['password_hash']);

$_SESSION['auth.user'] = $userRow;
$sessionToken = bin2hex(random_bytes(32));
$users->storeSession(
    (int) $userRow['id'],
    hash('sha256', $sessionToken),
    '127.0.0.1',
    'Performance Report Script',
    date('Y-m-d H:i:s', time() + 3600)
);
$_SESSION['auth.session_token'] = $sessionToken;

$records = [];

foreach ($cases as $case) {
    $query = $case['query'];
    $requestUri = $case['uri'] . ($query === [] ? '' : '?' . http_build_query($query));
    $_SERVER = [
        'REQUEST_METHOD' => $case['method'],
        'REQUEST_URI' => $requestUri,
        'REMOTE_ADDR' => '127.0.0.1',
        'HTTP_USER_AGENT' => 'Performance Report Script',
        'HTTP_ACCEPT' => 'application/json',
    ];
    $_GET = $query;
    $_POST = [];
    $_COOKIE = [];

    header_remove();
    http_response_code(200);
    Response::resetSentHeaders();

    $request = new Request($_SERVER, $query, [], [], []);
    $router = new Router();
    require dirname(__DIR__, 2) . '/routes/web.php';
    require dirname(__DIR__, 2) . '/routes/api.php';

    ob_start();
    $router->dispatch($request);
    ob_end_clean();

    $headers = Response::sentHeaders();

    $records[] = [
        'label' => $case['label'],
        'status' => http_response_code(),
        'headers' => $headers,
    ];
}

echo PerformanceReportFormatter::markdown($records, $heading);
