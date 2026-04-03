<?php

declare(strict_types=1);

namespace Tests\Services\Reports;

use App\Services\AnimalService;
use App\Services\Reports\AnimalDossierService;
use PHPUnit\Framework\TestCase;

final class AnimalDossierServiceTest extends TestCase
{
    public function testAssembleAddsAdoptionBillingAndAuditContextToAnimal(): void
    {
        $animals = $this->createMock(AnimalService::class);
        $animals->expects(self::once())
            ->method('get')
            ->with('18', true)
            ->willReturn([
                'id' => 18,
                'animal_id' => 'AN-18',
                'name' => 'Buddy',
            ]);

        $singleRows = [
            'application' => ['id' => 30, 'application_number' => 'APP-30'],
            'completion' => ['id' => 31, 'processed_by_name' => 'Kenneth Laron'],
        ];

        $listRows = [
            'invoices' => [['id' => 41, 'invoice_number' => 'INV-41']],
            'payments' => [['id' => 51, 'payment_number' => 'PAY-51']],
            'audit' => [[
                'id' => 61,
                'old_values' => '{"status":"Available"}',
                'new_values' => '{"status":"Adopted"}',
            ]],
        ];

        $service = new AnimalDossierService(
            $animals,
            function (string $key, int $animalId) use (&$singleRows): array|null {
                self::assertSame(18, $animalId);
                return $singleRows[$key] ?? null;
            },
            function (string $key, int $animalId) use (&$listRows): array {
                self::assertSame(18, $animalId);
                return $listRows[$key] ?? [];
            }
        );

        $dossier = $service->assemble(18);

        self::assertSame('APP-30', $dossier['adoption_application']['application_number']);
        self::assertSame('INV-41', $dossier['invoices'][0]['invoice_number']);
        self::assertSame('PAY-51', $dossier['payments'][0]['payment_number']);
        self::assertSame(['status' => 'Available'], $dossier['audit_trail'][0]['old_values']);
        self::assertSame(['status' => 'Adopted'], $dossier['audit_trail'][0]['new_values']);
    }
}
