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
use App\Models\User;
use App\Services\Adoption\AdoptionBillingSummary;
use App\Services\Adoption\AdoptionPortalService;
use App\Services\Adoption\AdoptionReadService;
use App\Services\Adoption\AdoptionStatusPolicy;
use App\Services\AuditService;
use App\Services\NotificationService;
use Tests\Integration\DatabaseIntegrationTestCase;

final class AdoptionPortalServiceIntegrationTest extends DatabaseIntegrationTestCase
{
    public function testRegisterAdopterCreatesUserAndAdminNotification(): void
    {
        $admin = $this->createUser('super_admin');
        $request = $this->makeRequest();

        $user = $this->portalService()->registerAdopter([
            'email' => 'portal.register.' . bin2hex(random_bytes(4)) . '@example.test',
            'password' => 'PortalPass!123',
            'first_name' => 'Portal',
            'last_name' => 'Registrant',
            'middle_name' => '',
            'phone' => '09171234567',
            'address_line1' => 'Main Street',
            'address_line2' => '',
            'city' => 'Catarman',
            'province' => 'Northern Samar',
            'zip_code' => '6400',
        ], $request);

        self::assertSame('adopter', $user['role_name']);
        self::assertStringStartsWith('adopter-', (string) ($user['username'] ?? ''));

        $adminNotification = Database::fetch(
            'SELECT title, message
             FROM notifications
             WHERE user_id = :user_id
               AND title = :title
             ORDER BY id DESC
             LIMIT 1',
            [
                'user_id' => $admin['id'],
                'title' => 'New Adopter Registration',
            ]
        );

        self::assertIsArray($adminNotification);
        self::assertStringContainsString('Portal Registrant', (string) $adminNotification['message']);
    }

    public function testSubmitPortalApplicationCreatesApplicationNotificationsAndStoredDocument(): void
    {
        $adopter = $this->createUser('adopter');
        $admin = $this->createUser('super_admin');
        $animal = $this->createAnimal();
        $uploadPath = tempnam(sys_get_temp_dir(), 'portal-valid-id-');
        self::assertNotFalse($uploadPath);
        file_put_contents($uploadPath, 'portal-valid-id');

        $application = $this->portalService()->submitPortalApplication(
            (int) $adopter['id'],
            [
                'animal_id' => (string) $animal['id'],
                'preferred_species' => 'Dog',
                'preferred_breed' => 'Mixed Breed',
                'preferred_age_min' => '1',
                'preferred_age_max' => '5',
                'preferred_size' => 'Medium',
                'preferred_gender' => 'Male',
                'housing_type' => 'House',
                'housing_ownership' => 'Owned',
                'has_yard' => '1',
                'yard_size' => 'Medium',
                'num_adults' => '2',
                'num_children' => '1',
                'children_ages' => '10',
                'existing_pets_description' => 'One vaccinated dog.',
                'previous_pet_experience' => 'Cares for rescued dogs.',
                'vet_reference_name' => 'Dr. Integration',
                'vet_reference_clinic' => 'Integration Vet Clinic',
                'vet_reference_contact' => '09171234568',
                'agrees_to_policies' => '1',
                'agrees_to_home_visit' => '1',
                'agrees_to_return_policy' => '1',
            ],
            [
                'name' => 'valid-id.png',
                'tmp_name' => $uploadPath,
                'size' => filesize($uploadPath),
                'error' => UPLOAD_ERR_OK,
            ],
            $this->makeRequest()
        );

        $this->trackRelativePath($application['valid_id_path'] ?? null);

        self::assertSame('pending_review', $application['status']);
        self::assertSame((int) $adopter['id'], (int) $application['adopter_id']);
        self::assertSame((int) $animal['id'], (int) $application['animal_id']);
        self::assertNotEmpty($application['valid_id_path']);

        $files = json_decode((string) ($application['valid_id_path'] ?? '[]'), true);
        self::assertIsArray($files);
        self::assertNotEmpty($files);

        $storedDocument = $this->absolutePathFor($files[0]);
        self::assertFileExists($storedDocument);

        $adopterNotification = Database::fetch(
            'SELECT title, message
             FROM notifications
             WHERE user_id = :user_id
               AND type = :type
             ORDER BY id DESC
             LIMIT 1',
            [
                'user_id' => $adopter['id'],
                'type' => 'adoption_application',
            ]
        );
        self::assertIsArray($adopterNotification);
        self::assertSame('Application received', $adopterNotification['title']);

        $adminNotification = Database::fetch(
            'SELECT title
             FROM notifications
             WHERE user_id = :user_id
               AND title = :title
             ORDER BY id DESC
             LIMIT 1',
            [
                'user_id' => $admin['id'],
                'title' => 'New Adoption Application',
            ]
        );
        self::assertIsArray($adminNotification);

        $auditLog = Database::fetch(
            'SELECT action, record_table
             FROM audit_logs
             WHERE module = :module
               AND record_table = :record_table
               AND record_id = :record_id
             ORDER BY id DESC
             LIMIT 1',
            [
                'module' => 'adoptions',
                'record_table' => 'adoption_applications',
                'record_id' => $application['id'],
            ]
        );
        self::assertIsArray($auditLog);
        self::assertSame('create', $auditLog['action']);
    }

    private function portalService(): AdoptionPortalService
    {
        return new AdoptionPortalService(
            new AdoptionApplication(),
            new Animal(),
            new User(),
            $this->readService(),
            new AuditService(new \App\Models\AuditLog(), new \App\Core\Logger()),
            new NotificationService(new \App\Models\Notification(), new \App\Models\User()),
            new \App\Models\Role()
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
            new AdoptionBillingSummary(),
            new User()
        );
    }
}
