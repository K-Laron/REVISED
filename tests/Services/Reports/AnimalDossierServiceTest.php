<?php

declare(strict_types=1);

namespace Tests\Services\Reports;

use App\Models\AdoptionApplication;
use App\Models\AdoptionCompletion;
use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\Payment;
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

        $adoptions = $this->createMock(AdoptionApplication::class);
        $adoptions->method('findLatestByAnimal')->with(18)->willReturn([
            'id' => 30,
            'application_number' => 'APP-30',
        ]);

        $completions = $this->createMock(AdoptionCompletion::class);
        $completions->method('findByAnimal')->with(18)->willReturn([
            'id' => 31,
            'processed_by_name' => 'Kenneth Laron',
        ]);

        $invoices = $this->createMock(Invoice::class);
        $invoices->method('listByAnimal')->with(18)->willReturn([
            ['id' => 41, 'invoice_number' => 'INV-41'],
        ]);

        $payments = $this->createMock(Payment::class);
        $payments->method('listByAnimalAcrossInvoices')->with(18)->willReturn([
            ['id' => 51, 'payment_number' => 'PAY-51'],
        ]);

        $audit = $this->createMock(AuditLog::class);
        $audit->method('listForAnimalDossier')->with(18)->willReturn([
            [
                'id' => 61,
                'old_values' => '{"status":"Available"}',
                'new_values' => '{"status":"Adopted"}',
            ],
        ]);

        $service = new AnimalDossierService(
            $animals,
            $adoptions,
            $completions,
            $invoices,
            $payments,
            $audit
        );

        $dossier = $service->assemble(18);

        self::assertSame('APP-30', $dossier['adoption_application']['application_number']);
        self::assertSame('INV-41', $dossier['invoices'][0]['invoice_number']);
        self::assertSame('PAY-51', $dossier['payments'][0]['payment_number']);
        self::assertSame(['status' => 'Available'], $dossier['audit_trail'][0]['old_values']);
        self::assertSame(['status' => 'Adopted'], $dossier['audit_trail'][0]['new_values']);
    }
}
