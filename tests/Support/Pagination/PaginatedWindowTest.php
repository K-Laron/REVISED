<?php

declare(strict_types=1);

namespace Tests\Support\Pagination;

use App\Support\Pagination\PaginatedWindow;
use PHPUnit\Framework\TestCase;

final class PaginatedWindowTest extends TestCase
{
    public function testResolveReturnsDerivedTotalWithoutCallingCounterOnLastPage(): void
    {
        $counterCalls = 0;

        $result = PaginatedWindow::resolve(
            rows: [['id' => 1], ['id' => 2]],
            page: 2,
            perPage: 5,
            counter: static function () use (&$counterCalls): int {
                $counterCalls++;

                return 7;
            }
        );

        self::assertSame(7, $result['total']);
        self::assertSame(0, $counterCalls);
    }

    public function testResolveCallsCounterWhenTheWindowOverflows(): void
    {
        $counterCalls = 0;

        $result = PaginatedWindow::resolve(
            rows: [['id' => 1], ['id' => 2], ['id' => 3], ['id' => 4]],
            page: 1,
            perPage: 3,
            counter: static function () use (&$counterCalls): int {
                $counterCalls++;

                return 9;
            }
        );

        self::assertSame(9, $result['total']);
        self::assertCount(3, $result['items']);
        self::assertSame(1, $counterCalls);
    }
}
