<?php

declare(strict_types=1);

namespace Tests\Services\Reports;

use PHPUnit\Framework\TestCase;

final class ReportRefactorAdoptionTest extends TestCase
{
    public function testReportServiceDelegatesDossierAndRangeWorkToFocusedCollaborators(): void
    {
        $source = (string) file_get_contents('C:\\Users\\TESS LARON\\Desktop\\REVISED\\src\\Services\\ReportService.php');

        self::assertStringContainsString('AnimalDossierService', $source);
        self::assertStringContainsString('ReportRange', $source);
    }
}
