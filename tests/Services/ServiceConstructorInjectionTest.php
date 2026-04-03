<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Models\AnimalQrCode;
use App\Models\Animal;
use App\Models\AnimalPhoto;
use App\Models\AuditLog;
use App\Models\SystemBackup;
use App\Models\Breed;
use App\Models\FeeSchedule;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\MedicalRecord;
use App\Models\MedicalLabResult;
use App\Models\MedicalPrescription;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\ReportTemplate;
use App\Models\Role;
use App\Models\StockTransaction;
use App\Models\User;
use App\Models\DewormingRecord;
use App\Models\EuthanasiaRecord;
use App\Models\ExaminationRecord;
use App\Models\Kennel;
use App\Models\KennelAssignment;
use App\Models\KennelMaintenanceLog;
use App\Models\SurgeryRecord;
use App\Models\TreatmentRecord;
use App\Models\VaccinationRecord;
use App\Models\VitalSign;
use App\Services\Adoption\AdoptionPortalService;
use App\Services\Adoption\AdoptionReadService;
use App\Services\Adoption\AdoptionWorkflowService;
use App\Services\AdoptionService;
use App\Services\AnimalService;
use App\Services\AuditService;
use App\Services\AuthService;
use App\Services\BackupService;
use App\Services\Billing\BillingDocumentManager;
use App\Services\Billing\BillingNotificationDispatcher;
use App\Services\Billing\InvoiceComputation;
use App\Services\BillingService;
use App\Services\ExportService;
use App\Services\InventoryService;
use App\Services\KennelService;
use App\Services\Medical\MedicalAnimalStatusSynchronizer;
use App\Services\Medical\MedicalAttachmentManager;
use App\Services\Medical\MedicalPayloadFactory;
use App\Services\Medical\MedicalProcedureConfig;
use App\Services\Medical\MedicalSharedSectionPersister;
use App\Services\Medical\MedicalSubtypePersister;
use App\Services\Medical\TreatmentInventorySynchronizer;
use App\Services\MedicalService;
use App\Services\PdfService;
use App\Services\QrCodeService;
use App\Services\ReportService;
use App\Services\Reports\AnimalDossierService;
use App\Services\SystemSettingsService;
use App\Services\UserService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class ServiceConstructorInjectionTest extends TestCase
{
    public function testAdoptionServiceAcceptsInjectedCollaborators(): void
    {
        $reads = $this->createMock(AdoptionReadService::class);
        $portal = $this->createMock(AdoptionPortalService::class);
        $workflow = $this->createMock(AdoptionWorkflowService::class);

        $service = new AdoptionService($reads, $portal, $workflow);

        self::assertSame($reads, $this->serviceProperty($service, 'reads'));
        self::assertSame($portal, $this->serviceProperty($service, 'portal'));
        self::assertSame($workflow, $this->serviceProperty($service, 'workflow'));
    }

    public function testAuthServiceAcceptsInjectedCollaborators(): void
    {
        $users = $this->createMock(User::class);

        $service = new AuthService($users);

        self::assertSame($users, $this->serviceProperty($service, 'users'));
    }

    public function testUserServiceAcceptsInjectedCollaborators(): void
    {
        $users = $this->createMock(User::class);
        $roles = $this->createMock(Role::class);
        $permissions = $this->createMock(Permission::class);
        $audit = $this->createMock(AuditService::class);
        $notifications = $this->createMock(\App\Services\NotificationService::class);

        $service = new UserService($users, $roles, $permissions, $audit, $notifications);

        self::assertSame($users, $this->serviceProperty($service, 'users'));
        self::assertSame($roles, $this->serviceProperty($service, 'roles'));
        self::assertSame($permissions, $this->serviceProperty($service, 'permissions'));
        self::assertSame($audit, $this->serviceProperty($service, 'audit'));
        self::assertSame($notifications, $this->serviceProperty($service, 'notifications'));
    }

    public function testAnimalServiceAcceptsInjectedCollaborators(): void
    {
        $animals = $this->createMock(Animal::class);
        $breeds = $this->createMock(Breed::class);
        $photos = $this->createMock(AnimalPhoto::class);
        $qrCodes = $this->createMock(QrCodeService::class);
        $audit = $this->createMock(AuditService::class);

        $service = new AnimalService($animals, $breeds, $photos, $qrCodes, $audit);

        self::assertSame($animals, $this->serviceProperty($service, 'animals'));
        self::assertSame($breeds, $this->serviceProperty($service, 'breeds'));
        self::assertSame($photos, $this->serviceProperty($service, 'photos'));
        self::assertSame($qrCodes, $this->serviceProperty($service, 'qrCodes'));
        self::assertSame($audit, $this->serviceProperty($service, 'audit'));
    }

    public function testInventoryServiceAcceptsInjectedCollaborators(): void
    {
        $items = $this->createMock(InventoryItem::class);
        $categories = $this->createMock(InventoryCategory::class);
        $transactions = $this->createMock(StockTransaction::class);
        $audit = $this->createMock(AuditService::class);

        $service = new InventoryService($items, $categories, $transactions, $audit);

        self::assertSame($items, $this->serviceProperty($service, 'items'));
        self::assertSame($categories, $this->serviceProperty($service, 'categories'));
        self::assertSame($transactions, $this->serviceProperty($service, 'transactions'));
        self::assertSame($audit, $this->serviceProperty($service, 'audit'));
    }

    public function testBillingServiceAcceptsInjectedCollaborators(): void
    {
        $invoices = $this->createMock(Invoice::class);
        $lineItems = $this->createMock(InvoiceLineItem::class);
        $payments = $this->createMock(Payment::class);
        $fees = $this->createMock(FeeSchedule::class);
        $pdfs = $this->createMock(PdfService::class);
        $audit = $this->createMock(AuditService::class);
        $computation = $this->createMock(InvoiceComputation::class);
        $documents = $this->createMock(BillingDocumentManager::class);
        $notifications = $this->createMock(BillingNotificationDispatcher::class);

        $service = new BillingService(
            $invoices,
            $lineItems,
            $payments,
            $fees,
            $pdfs,
            $audit,
            $computation,
            $documents,
            $notifications
        );

        self::assertSame($invoices, $this->serviceProperty($service, 'invoices'));
        self::assertSame($lineItems, $this->serviceProperty($service, 'lineItems'));
        self::assertSame($payments, $this->serviceProperty($service, 'payments'));
        self::assertSame($fees, $this->serviceProperty($service, 'fees'));
        self::assertSame($pdfs, $this->serviceProperty($service, 'pdfs'));
        self::assertSame($audit, $this->serviceProperty($service, 'audit'));
        self::assertSame($computation, $this->serviceProperty($service, 'computation'));
        self::assertSame($documents, $this->serviceProperty($service, 'documents'));
        self::assertSame($notifications, $this->serviceProperty($service, 'notifications'));
    }

    public function testMedicalServiceAcceptsInjectedCollaborators(): void
    {
        $records = $this->createMock(MedicalRecord::class);
        $animals = $this->createMock(Animal::class);
        $audit = $this->createMock(AuditService::class);
        $procedureConfig = $this->createMock(MedicalProcedureConfig::class);
        $payloadFactory = $this->createMock(MedicalPayloadFactory::class);
        $treatmentInventory = $this->createMock(TreatmentInventorySynchronizer::class);
        $subtypes = new MedicalSubtypePersister(
            $this->createMock(VaccinationRecord::class),
            $this->createMock(SurgeryRecord::class),
            $this->createMock(ExaminationRecord::class),
            $this->createMock(TreatmentRecord::class),
            $this->createMock(DewormingRecord::class),
            $this->createMock(EuthanasiaRecord::class)
        );
        $attachments = $this->createMock(MedicalAttachmentManager::class);
        $sharedSections = new MedicalSharedSectionPersister(
            $this->createMock(VitalSign::class),
            $this->createMock(MedicalPrescription::class),
            $this->createMock(MedicalLabResult::class),
            $attachments
        );
        $animalStatus = new MedicalAnimalStatusSynchronizer($animals);

        $service = new MedicalService(
            $records,
            $animals,
            $audit,
            $procedureConfig,
            $payloadFactory,
            $treatmentInventory,
            $subtypes,
            $sharedSections,
            $attachments,
            $animalStatus
        );

        self::assertSame($records, $this->serviceProperty($service, 'records'));
        self::assertSame($animals, $this->serviceProperty($service, 'animals'));
        self::assertSame($audit, $this->serviceProperty($service, 'audit'));
        self::assertSame($procedureConfig, $this->serviceProperty($service, 'procedureConfig'));
        self::assertSame($payloadFactory, $this->serviceProperty($service, 'payloadFactory'));
        self::assertSame($treatmentInventory, $this->serviceProperty($service, 'treatmentInventory'));
        self::assertSame($subtypes, $this->serviceProperty($service, 'subtypes'));
        self::assertSame($sharedSections, $this->serviceProperty($service, 'sharedSections'));
        self::assertSame($attachments, $this->serviceProperty($service, 'attachments'));
        self::assertSame($animalStatus, $this->serviceProperty($service, 'animalStatus'));
    }

    public function testReportServiceAcceptsInjectedCollaborators(): void
    {
        $auditLogs = $this->createMock(AuditLog::class);
        $templates = $this->createMock(ReportTemplate::class);
        $dossiers = new AnimalDossierService(
            $this->createMock(AnimalService::class),
            static fn (): array|false => [],
            static fn (): array => []
        );

        $service = new ReportService($auditLogs, $templates, $dossiers);

        self::assertSame($auditLogs, $this->serviceProperty($service, 'auditLogs'));
        self::assertSame($templates, $this->serviceProperty($service, 'templates'));
        self::assertSame($dossiers, $this->serviceProperty($service, 'dossiers'));
    }

    public function testQrCodeServiceAcceptsInjectedCollaborators(): void
    {
        $qrCodes = $this->createMock(AnimalQrCode::class);
        $animals = $this->createMock(Animal::class);

        $service = new QrCodeService($qrCodes, $animals);

        self::assertSame($qrCodes, $this->serviceProperty($service, 'qrCodes'));
        self::assertSame($animals, $this->serviceProperty($service, 'animals'));
    }

    public function testKennelServiceAcceptsInjectedCollaborators(): void
    {
        $kennels = $this->createMock(Kennel::class);
        $assignments = $this->createMock(KennelAssignment::class);
        $maintenance = $this->createMock(KennelMaintenanceLog::class);
        $animals = $this->createMock(Animal::class);
        $audit = $this->createMock(AuditService::class);

        $service = new KennelService($kennels, $assignments, $maintenance, $animals, $audit);

        self::assertSame($kennels, $this->serviceProperty($service, 'kennels'));
        self::assertSame($assignments, $this->serviceProperty($service, 'assignments'));
        self::assertSame($maintenance, $this->serviceProperty($service, 'maintenance'));
        self::assertSame($animals, $this->serviceProperty($service, 'animals'));
        self::assertSame($audit, $this->serviceProperty($service, 'audit'));
    }

    public function testSystemSettingsServiceAcceptsInjectedCollaborators(): void
    {
        $audit = $this->createMock(AuditService::class);

        $service = new SystemSettingsService($audit);

        self::assertSame($audit, $this->serviceProperty($service, 'audit'));
    }

    public function testBackupServiceAcceptsInjectedCollaborators(): void
    {
        $backups = $this->createMock(SystemBackup::class);
        $audit = $this->createMock(AuditService::class);

        $service = new BackupService($backups, $audit);

        self::assertSame($backups, $this->serviceProperty($service, 'backups'));
        self::assertSame($audit, $this->serviceProperty($service, 'audit'));
    }

    public function testExportServiceAcceptsInjectedCollaborators(): void
    {
        $pdfs = $this->createMock(PdfService::class);

        $service = new ExportService($pdfs);

        self::assertSame($pdfs, $this->serviceProperty($service, 'pdfs'));
    }

    private function serviceProperty(object $service, string $name): mixed
    {
        $reflection = new ReflectionClass($service);
        $property = $reflection->getProperty($name);

        return $property->getValue($service);
    }
}
