<?php

declare(strict_types=1);

namespace App\Services\Search;

interface SearchProviderInterface
{
    public function key(): string;

    public function label(): string;

    public function permission(): string;

    public function search(string $term, int $limit, array $filters): array;
}
