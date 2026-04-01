<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Support\Breadcrumbs;
use PHPUnit\Framework\TestCase;

final class BreadcrumbsTest extends TestCase
{
    public function testEnhanceAuthenticatedTrailBuildsClickableHierarchyFromPath(): void
    {
        $html = Breadcrumbs::enhanceAuthenticatedTrail(
            '<div class="breadcrumb">Home &gt; Animals &gt; AN-2026-0012 &gt; Edit</div>',
            '/animals/42/edit'
        );

        self::assertStringContainsString('href="/dashboard"', $html);
        self::assertStringContainsString('href="/animals"', $html);
        self::assertStringContainsString('href="/animals/42"', $html);
        self::assertStringContainsString('<span class="breadcrumb-current" aria-current="page">Edit</span>', $html);
    }

    public function testEnhanceAuthenticatedTrailKeepsNestedBuilderPagesPointingAtTheirModuleRoot(): void
    {
        $html = Breadcrumbs::enhanceAuthenticatedTrail(
            '<div class="breadcrumb">Home &gt; Billing &gt; Create Invoice</div>',
            '/billing/invoices/create'
        );

        self::assertStringContainsString('href="/dashboard"', $html);
        self::assertStringContainsString('href="/billing"', $html);
        self::assertStringNotContainsString('href="/billing/invoices"', $html);
        self::assertStringContainsString('<span class="breadcrumb-current" aria-current="page">Create Invoice</span>', $html);
    }
}
