<?php

declare(strict_types=1);

namespace App\Support;

final class Breadcrumbs
{
    public static function enhanceAuthenticatedContent(string $content, string $requestUri): string
    {
        return preg_replace_callback(
            '/<div class="breadcrumb">.*?<\/div>/s',
            static fn (array $matches): string => self::enhanceAuthenticatedTrail($matches[0], $requestUri),
            $content
        ) ?? $content;
    }

    public static function enhanceAuthenticatedTrail(string $breadcrumbHtml, string $requestUri): string
    {
        $innerHtml = self::extractInnerHtml($breadcrumbHtml);
        $segments = self::segments($innerHtml);

        if ($segments === []) {
            return $breadcrumbHtml;
        }

        $targets = self::targets($requestUri, count($segments));
        $markup = ['<nav class="breadcrumb" aria-label="Breadcrumb" data-breadcrumb-trail="authenticated">'];

        foreach ($segments as $index => $segment) {
            if ($index > 0) {
                $markup[] = '<span class="breadcrumb-separator" aria-hidden="true">&gt;</span>';
            }

            $escapedSegment = htmlspecialchars($segment, ENT_QUOTES, 'UTF-8');
            $isCurrent = $index === count($segments) - 1;

            if (!$isCurrent && isset($targets[$index])) {
                $markup[] = sprintf(
                    '<a class="breadcrumb-link" href="%s" data-breadcrumb-link="true" data-breadcrumb-index="%d">%s</a>',
                    htmlspecialchars($targets[$index], ENT_QUOTES, 'UTF-8'),
                    $index,
                    $escapedSegment
                );
                continue;
            }

            $markup[] = sprintf(
                '<span class="breadcrumb-current"%s>%s</span>',
                $isCurrent ? ' aria-current="page"' : '',
                $escapedSegment
            );
        }

        $markup[] = '</nav>';

        return implode('', $markup);
    }

    private static function extractInnerHtml(string $breadcrumbHtml): string
    {
        if (!preg_match('/<div class="breadcrumb">(?P<inner>.*?)<\/div>/s', $breadcrumbHtml, $matches)) {
            return $breadcrumbHtml;
        }

        return $matches['inner'];
    }

    /**
     * @return list<string>
     */
    private static function segments(string $innerHtml): array
    {
        $decoded = html_entity_decode(strip_tags($innerHtml), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $parts = preg_split('/\s*>\s*/u', $decoded) ?: [];

        return array_values(array_filter(array_map(
            static function (string $part): string {
                return trim((string) preg_replace('/\s+/u', ' ', $part));
            },
            $parts
        ), static fn (string $part): bool => $part !== ''));
    }

    /**
     * @return array<int, string>
     */
    private static function targets(string $requestUri, int $segmentCount): array
    {
        $requestPath = parse_url($requestUri, PHP_URL_PATH) ?: '/dashboard';
        $pathSegments = array_values(array_filter(explode('/', trim($requestPath, '/')), static fn (string $segment): bool => $segment !== ''));
        $targets = [];

        if ($segmentCount <= 1) {
            return $targets;
        }

        $targets[0] = '/dashboard';

        if ($segmentCount === 2 || ($pathSegments[0] ?? '') === '') {
            return $targets;
        }

        $targets[1] = '/' . $pathSegments[0];
        $maxPrefix = max(1, count($pathSegments) - 1);

        for ($index = 2; $index < $segmentCount - 1; $index++) {
            $prefixLength = min($index, $maxPrefix);
            $prefix = array_slice($pathSegments, 0, $prefixLength);

            if ($prefix === []) {
                continue;
            }

            $targets[$index] = '/' . implode('/', $prefix);
        }

        return $targets;
    }
}
