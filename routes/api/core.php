<?php

declare(strict_types=1);

$router->get('/api/ping', static function () {
    return \App\Core\Response::success([
        'timestamp' => date(DATE_ATOM),
        'status' => 'ok',
    ], 'API is reachable.');
});

if (filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOL)) {
    $router->post('/api/validate-test', static function ($request) {
        $validator = new \App\Helpers\Validator($request->body());
        $validator->rules([
            'email' => 'required|email|max:255',
            'name' => 'required|string|min:2|max:100',
        ]);

        if ($validator->fails()) {
            return \App\Core\Response::error(422, 'VALIDATION_ERROR', 'The given data was invalid.', $validator->errors());
        }

        return \App\Core\Response::success($request->body(), 'Validation passed.');
    });
}
