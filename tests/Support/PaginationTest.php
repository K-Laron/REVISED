<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Support\Pagination;
use PHPUnit\Framework\TestCase;

final class PaginationTest extends TestCase
{
    public function testPageClampsValuesBelowOne(): void
    {
        self::assertSame(1, Pagination::page(null));
        self::assertSame(1, Pagination::page('0'));
        self::assertSame(5, Pagination::page('5'));
    }

    public function testPerPageUsesDefaultCapAndMinimum(): void
    {
        self::assertSame(20, Pagination::perPage(null, 20));
        self::assertSame(1, Pagination::perPage('invalid', 20));
        self::assertSame(50, Pagination::perPage('99', 10, 50));
    }

    public function testMetaBuildsConsistentPaginationPayload(): void
    {
        self::assertSame(
            [
                'page' => 2,
                'per_page' => 20,
                'total' => 41,
                'total_pages' => 3,
            ],
            Pagination::meta(2, 20, 41)
        );
    }
}
