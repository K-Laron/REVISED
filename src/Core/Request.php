<?php

declare(strict_types=1);

namespace App\Core;

use App\Helpers\Sanitizer;
use App\Support\ProxyTrust;

class Request
{
    private array $routeParams = [];
    private array $attributes = [];

    public function __construct(
        private readonly array $server,
        private readonly array $query,
        private array $body,
        private readonly array $files,
        private readonly array $cookies
    ) {
    }

    public static function capture(): self
    {
        $body = [];
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';

        if (str_contains($contentType, 'application/json')) {
            $body = json_decode(file_get_contents('php://input') ?: '[]', true) ?: [];
        } elseif (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $body = $_POST;

            if ($body === []) {
                parse_str(file_get_contents('php://input') ?: '', $body);
            }
        }

        return new self(
            $_SERVER,
            $_GET,
            Sanitizer::cleanArray($body),
            $_FILES,
            $_COOKIE
        );
    }

    public function method(): string
    {
        $method = strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');

        if ($method === 'POST') {
            $override = strtoupper((string) ($this->body['_method'] ?? ''));
            if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
                return $override;
            }
        }

        return $method;
    }

    public function path(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);

        return $path === '' ? '/' : (rtrim($path ?: '/', '/') ?: '/');
    }

    public function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->query;
        }

        return $this->query[$key] ?? $default;
    }

    public function body(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->body;
        }

        return $this->body[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    public function file(?string $key = null): mixed
    {
        if ($key === null) {
            return $this->files;
        }

        $file = $this->files[$key] ?? null;
        if ($file === null) {
            return null;
        }

        // Handle multiple files in PHP's non-standard $_FILES format
        if (isset($file['name']) && is_array($file['name'])) {
            $files = [];
            foreach ($file['name'] as $idx => $name) {
                if ($name === '') continue; // Skip empty slots
                $files[] = [
                    'name' => $name,
                    'type' => $file['type'][$idx] ?? null,
                    'tmp_name' => $file['tmp_name'][$idx] ?? null,
                    'error' => $file['error'][$idx] ?? null,
                    'size' => $file['size'][$idx] ?? null,
                ];
            }
            return $files;
        }

        return $file;
    }

    public function header(string $key, mixed $default = null): mixed
    {
        $normalized = 'HTTP_' . strtoupper(str_replace('-', '_', $key));

        return $this->server[$normalized] ?? $this->server[$key] ?? $default;
    }

    public function cookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    public function route(string $key, mixed $default = null): mixed
    {
        return $this->routeParams[$key] ?? $default;
    }

    public function routeParams(): array
    {
        return $this->routeParams;
    }

    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function attribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    public function isJson(): bool
    {
        $contentType = $this->header('Content-Type', '');

        return is_string($contentType) && str_contains($contentType, 'application/json');
    }

    public function expectsJson(): bool
    {
        $accept = $this->header('Accept', '');

        return str_contains((string) $accept, 'application/json') || str_starts_with($this->path(), '/api/');
    }

    public function ip(): string
    {
        return ProxyTrust::clientIp($this->server);
    }

    public function userAgent(): string
    {
        return $this->server['HTTP_USER_AGENT'] ?? 'unknown';
    }

    public function isSecure(): bool
    {
        return ProxyTrust::isSecureRequest($this->server);
    }
}
