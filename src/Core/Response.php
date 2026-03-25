<?php

declare(strict_types=1);

namespace App\Core;

class Response
{
    public function __construct(
        private readonly int $status,
        private readonly string $content,
        private readonly array $headers = []
    ) {
    }

    public static function json(int $status, array $payload): self
    {
        return new self($status, json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}', [
            'Content-Type' => 'application/json; charset=utf-8',
        ]);
    }

    public static function success(array $data = [], string $message = 'Request completed successfully.', array $meta = []): self
    {
        return self::json(200, [
            'success' => true,
            'data' => $data === [] ? (object) [] : $data,
            'meta' => $meta === [] ? (object) [] : $meta,
            'message' => $message,
        ]);
    }

    public static function error(int $status, string $code, string $message, array $details = []): self
    {
        return self::json($status, [
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details === [] ? (object) [] : $details,
            ],
        ]);
    }

    public static function html(string $content, int $status = 200, array $headers = []): self
    {
        return new self($status, $content, $headers + ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public static function redirect(string $location, int $status = 302): self
    {
        return new self($status, '', ['Location' => $location]);
    }

    public function send(): void
    {
        http_response_code($this->status);

        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }

        echo $this->content;
    }
}
