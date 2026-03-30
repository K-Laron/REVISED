<?php

declare(strict_types=1);

namespace Tests\Integration\Adoption;

require_once __DIR__ . '/../DatabaseIntegrationTestCase.php';

use App\Core\Database;
use App\Models\AdoptionApplication;
use App\Models\AdoptionCompletion;
use App\Models\AdoptionInterview;
use App\Models\AdoptionSeminar;
use App\Models\Animal;
use App\Services\Adoption\AdoptionBillingSummary;
use App\Services\Adoption\AdoptionReadService;
use App\Services\Adoption\AdoptionStatusPolicy;
use App\Services\Adoption\AdoptionWorkflowService;
use App\Services\AuditService;
use App\Services\NotificationService;
use App\Services\PdfService;
use RuntimeException;
use Tests\Integration\DatabaseIntegrationTestCase;

final class AdoptionWorkflowServiceIntegrationTest extends DatabaseIntegrationTestCase
{
    public function testScheduleInterviewCreatesInterviewAndAdvancesApplicationStatus(): void
    {
        $staff = $this->createUser('shelter_staff');
        $adopter = $this->createUser('adopter');
        $animal = $this->createAnimal();
        $application = $this->createApplication([
            'adopter_id' => (int) $adopter['id'],
            'animal_id' => (int) $animal['id'],
            'status' => 'pending_review',
        ]);

        $updated = $this->workflowService()->scheduleInterview(
            (int) $application['id'],
            [
                'scheduled_date' => date('Y-m-d H:i:s', strtotime('+1 day')),
                'interview_type' => 'in_person',
                'video_call_link' => '',
                'location' => 'Conference Room',
                'conducted_by' => (string) $staff['id'],
            ],
            (int) $staff['id'],
            $this->makeRequest()
        );

        self::assertSame('interview_scheduled', $updated['status']);
        self::assertCount(1, $updated['interviews']);
        self::assertSame('scheduled', $updated['interviews'][0]['status']);

        $notification = Database::fetch(
            'SELECT title
             FROM notifications
             WHERE user_id = :user_id
               AND type = :type
             ORDER BY id DESC
             LIMIT 1',
            [
                'user_id' => $adopter['id'],
                'type' => 'interview_scheduled',
            ]
        );
        self::assertIsArray($notification);
        self::assertSame('Interview scheduled', $notification['title']);
    }

    public function testUpdateAttendanceMarksApplicationAsSeminarCompletedWhenBillingIsClear(): void
    {
        $staff = $this->createUser('shelter_staff');
        $adopter = $this->createUser('adopter');
        $animal = $this->createAnimal();
        $application = $this->createApplication([
            'adopter_id' => (int) $adopter['id'],
            'animal_id' => (int) $animal['id'],
            'status' => 'interview_completed',
        ]);
        $seminar = $this->createSeminar([
            'created_by' => (int) $staff['id'],
            'facilitator_id' => (int) $staff['id'],
        ]);

        $registered = $this->workflowService()->registerAttendee(
            (int) $seminar['id'],
            (int) $application['id'],
            (int) $staff['id'],
            $this->makeRequest()
        );
        self::assertSame('seminar_scheduled', $registered['status']);

        $updated = $this->workflowService()->updateAttendance(
            (int) $seminar['id'],
            (int) $application['id'],
            'attended',
            (int) $staff['id'],
            $this->makeRequest()
        );

        self::assertSame('seminar_completed', $updated['status']);

        $attendee = Database::fetch(
            'SELECT attendance_status
             FROM seminar_attendees
             WHERE seminar_id = :seminar_id
               AND application_id = :application_id
             LIMIT 1',
            [
                'seminar_id' => $seminar['id'],
                'application_id' => $application['id'],
            ]
        );
        self::assertIsArray($attendee);
        self::assertSame('attended', $attendee['attendance_status']);
    }

    public function testCompleteBlocksPendingInvoicesWithoutManualConfirmation(): void
    {
        $staff = $this->createUser('shelter_staff');
        $adopter = $this->createUser('adopter');
        $animal = $this->createAnimal();
        $application = $this->createApplication([
            'adopter_id' => (int) $adopter['id'],
            'animal_id' => (int) $animal['id'],
            'status' => 'pending_payment',
        ]);

        $this->createInvoice([
            'application_id' => (int) $application['id'],
            'animal_id' => (int) $animal['id'],
            'payor_user_id' => (int) $adopter['id'],
            'payor_name' => trim((string) $adopter['first_name'] . ' ' . $adopter['last_name']),
            'total_amount' => 750.0,
            'subtotal' => 750.0,
            'amount_paid' => 0.0,
            'payment_status' => 'unpaid',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Outstanding adoption billing must be settled or manually confirmed before completion.');

        $this->workflowService()->complete(
            (int) $application['id'],
            [
                'completion_date' => date('Y-m-d H:i:s'),
                'payment_confirmed' => false,
                'contract_signed' => true,
                'medical_records_provided' => true,
                'spay_neuter_agreement' => true,
                'notes' => 'Attempting completion without payment confirmation.',
            ],
            (int) $staff['id'],
            $this->makeRequest()
        );
    }

    public function testCompleteCreatesCompletionCertificateAndAnimalStatus(): void
    {
        $staff = $this->createUser('shelter_staff');
        $adopter = $this->createUser('adopter');
        $animal = $this->createAnimal();
        $application = $this->createApplication([
            'adopter_id' => (int) $adopter['id'],
            'animal_id' => (int) $animal['id'],
            'status' => 'seminar_completed',
        ]);

        $updated = $this->workflowService()->complete(
            (int) $application['id'],
            [
                'completion_date' => date('Y-m-d H:i:s'),
                'payment_confirmed' => true,
                'contract_signed' => true,
                'medical_records_provided' => true,
                'spay_neuter_agreement' => true,
                'notes' => 'Integration completion test.',
            ],
            (int) $staff['id'],
            $this->makeRequest()
        );

        self::assertSame('completed', $updated['status']);
        self::assertIsArray($updated['completion']);
        self::assertNotEmpty($updated['completion']['certificate_path']);

        $this->trackRelativePath($updated['completion']['certificate_path']);
        $certificatePath = $this->absolutePathFor((string) $updated['completion']['certificate_path']);
        self::assertFileExists($certificatePath);

        $animalRow = Database::fetch('SELECT status FROM animals WHERE id = :id LIMIT 1', ['id' => $animal['id']]);
        self::assertIsArray($animalRow);
        self::assertSame('Adopted', $animalRow['status']);
    }

    public function testUpdateStatusToCompletedRunsCompletionWorkflowAndUpdatesAnimalStatus(): void
    {
        $staff = $this->createUser('shelter_staff');
        $adopter = $this->createUser('adopter');
        $animal = $this->createAnimal();
        $application = $this->createApplication([
            'adopter_id' => (int) $adopter['id'],
            'animal_id' => (int) $animal['id'],
            'status' => 'seminar_completed',
        ]);

        $updated = $this->workflowService()->updateStatus(
            (int) $application['id'],
            'completed',
            (int) $staff['id'],
            $this->makeRequest()
        );

        self::assertSame('completed', $updated['status']);
        self::assertIsArray($updated['completion']);
        self::assertNotEmpty($updated['completion']['certificate_path']);

        $this->trackRelativePath($updated['completion']['certificate_path']);

        $animalRow = Database::fetch(
            'SELECT status
             FROM animals
             WHERE id = :id
             LIMIT 1',
            ['id' => $animal['id']]
        );
        self::assertIsArray($animalRow);
        self::assertSame('Adopted', $animalRow['status']);
    }

    private function workflowService(): AdoptionWorkflowService
    {
        return new AdoptionWorkflowService(
            new AdoptionApplication(),
            new AdoptionInterview(),
            new AdoptionSeminar(),
            new AdoptionCompletion(),
            new Animal(),
            $this->readService(),
            new AdoptionStatusPolicy(),
            new AdoptionBillingSummary(),
            new PdfService(),
            new AuditService(),
            new NotificationService()
        );
    }

    private function readService(): AdoptionReadService
    {
        return new AdoptionReadService(
            new AdoptionApplication(),
            new AdoptionInterview(),
            new AdoptionSeminar(),
            new AdoptionCompletion(),
            new AdoptionStatusPolicy(),
            new AdoptionBillingSummary()
        );
    }
}
