<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Core\Request;
use App\Models\User;
use PHPUnit\Framework\TestCase;
use Throwable;

abstract class DatabaseIntegrationTestCase extends TestCase
{
    private static bool $databaseReady = false;
    private static ?string $databaseError = null;

    /** @var string[] */
    private array $trackedFiles = [];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        try {
            Database::fetch('SELECT 1 AS ok');
            self::$databaseReady = true;
            self::$databaseError = null;
        } catch (Throwable $exception) {
            self::$databaseReady = false;
            self::$databaseError = $exception->getMessage();
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (!self::$databaseReady) {
            self::markTestSkipped('Database integration test skipped: ' . (self::$databaseError ?? 'Database unavailable.'));
        }

        Database::beginTransaction();
    }

    protected function tearDown(): void
    {
        foreach (array_reverse($this->trackedFiles) as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }

        Database::rollBack();
        parent::tearDown();
    }

    protected function makeRequest(
        array $body = [],
        array $query = [],
        array $files = [],
        array $attributes = [],
        array $server = []
    ): Request {
        $request = new Request(
            array_merge([
                'REQUEST_METHOD' => 'POST',
                'REQUEST_URI' => '/tests/integration',
                'REMOTE_ADDR' => '127.0.0.1',
                'HTTP_USER_AGENT' => 'PHPUnit Integration Test',
                'HTTP_ACCEPT' => 'application/json',
            ], $server),
            $query,
            $body,
            $files,
            []
        );

        foreach ($attributes as $key => $value) {
            $request->setAttribute($key, $value);
        }

        return $request;
    }

    protected function trackRelativePath(?string $relativePath): void
    {
        if ($relativePath === null || $relativePath === '') {
            return;
        }

        $this->trackedFiles[] = $this->absolutePathFor($relativePath);
    }

    protected function absolutePathFor(string $relativePath): string
    {
        $normalizedPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
        $root = dirname(__DIR__, 2);

        if (str_starts_with($normalizedPath, 'uploads' . DIRECTORY_SEPARATOR)) {
            return $root . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $normalizedPath;
        }

        return $root . DIRECTORY_SEPARATOR . $normalizedPath;
    }

    protected function roleId(string $roleName): int
    {
        $role = Database::fetch('SELECT id FROM roles WHERE name = :name LIMIT 1', ['name' => $roleName]);
        self::assertIsArray($role, 'Role "' . $roleName . '" must exist for integration tests.');

        return (int) $role['id'];
    }

    protected function createUser(string $roleName, array $overrides = []): array
    {
        $suffix = bin2hex(random_bytes(5));
        $payload = array_merge([
            'role_id' => $this->roleId($roleName),
            'email' => 'integration.' . $roleName . '.' . $suffix . '@example.test',
            'password_hash' => password_hash('IntegrationPass!123', PASSWORD_BCRYPT, ['cost' => 4]),
            'first_name' => 'Integration',
            'last_name' => ucfirst(str_replace('_', ' ', $roleName)),
            'middle_name' => null,
            'phone' => '09171234567',
            'address_line1' => 'Integration Street',
            'address_line2' => null,
            'city' => 'Catarman',
            'province' => 'Northern Samar',
            'zip_code' => '6400',
            'is_active' => 1,
            'email_verified_at' => date('Y-m-d H:i:s'),
            'force_password_change' => 0,
            'created_by' => null,
            'updated_by' => null,
        ], $overrides);

        Database::execute(
            'INSERT INTO users (
                role_id, email, password_hash, first_name, last_name, middle_name, phone,
                address_line1, address_line2, city, province, zip_code, is_active,
                email_verified_at, force_password_change, created_by, updated_by
             ) VALUES (
                :role_id, :email, :password_hash, :first_name, :last_name, :middle_name, :phone,
                :address_line1, :address_line2, :city, :province, :zip_code, :is_active,
                :email_verified_at, :force_password_change, :created_by, :updated_by
             )',
            $payload
        );

        $userId = (int) Database::lastInsertId();
        $users = new User();
        $users->assignGeneratedUsername($userId);

        return $users->findById($userId) ?: [];
    }

    protected function createAnimal(array $overrides = []): array
    {
        $suffix = bin2hex(random_bytes(4));
        $payload = array_merge([
            'animal_id' => 'IT-' . strtoupper($suffix),
            'name' => 'Integration Animal ' . strtoupper(substr($suffix, 0, 4)),
            'species' => 'Dog',
            'breed_id' => null,
            'breed_other' => null,
            'gender' => 'Male',
            'age_years' => 2,
            'age_months' => 0,
            'color_markings' => 'Brown',
            'size' => 'Medium',
            'weight_kg' => 12.5,
            'distinguishing_features' => null,
            'special_needs_notes' => null,
            'intake_type' => 'Stray',
            'intake_date' => date('Y-m-d H:i:s', strtotime('-2 days')),
            'location_found' => null,
            'barangay_of_origin' => null,
            'impoundment_order_number' => null,
            'authority_name' => null,
            'authority_position' => null,
            'authority_contact' => null,
            'brought_by_name' => null,
            'brought_by_contact' => null,
            'brought_by_address' => null,
            'impounding_officer_name' => null,
            'surrender_reason' => null,
            'condition_at_intake' => 'Healthy',
            'vaccination_status_at_intake' => 'Unknown',
            'temperament' => 'Friendly',
            'microchip_number' => null,
            'spay_neuter_status' => 'Unknown',
            'status' => 'Available',
            'status_reason' => null,
            'status_changed_at' => null,
            'outcome_date' => null,
            'created_by' => null,
            'updated_by' => null,
        ], $overrides);

        Database::execute(
            'INSERT INTO animals (
                animal_id, name, species, breed_id, breed_other, gender, age_years, age_months,
                color_markings, size, weight_kg, distinguishing_features, special_needs_notes,
                intake_type, intake_date, location_found, barangay_of_origin, impoundment_order_number,
                authority_name, authority_position, authority_contact, brought_by_name, brought_by_contact,
                brought_by_address, impounding_officer_name, surrender_reason, condition_at_intake,
                vaccination_status_at_intake, temperament, microchip_number, spay_neuter_status,
                status, status_reason, status_changed_at, outcome_date, created_by, updated_by
             ) VALUES (
                :animal_id, :name, :species, :breed_id, :breed_other, :gender, :age_years, :age_months,
                :color_markings, :size, :weight_kg, :distinguishing_features, :special_needs_notes,
                :intake_type, :intake_date, :location_found, :barangay_of_origin, :impoundment_order_number,
                :authority_name, :authority_position, :authority_contact, :brought_by_name, :brought_by_contact,
                :brought_by_address, :impounding_officer_name, :surrender_reason, :condition_at_intake,
                :vaccination_status_at_intake, :temperament, :microchip_number, :spay_neuter_status,
                :status, :status_reason, :status_changed_at, :outcome_date, :created_by, :updated_by
             )',
            $payload
        );

        $animalId = (int) Database::lastInsertId();

        return Database::fetch('SELECT * FROM animals WHERE id = :id LIMIT 1', ['id' => $animalId]) ?: [];
    }

    protected function createApplication(array $overrides = []): array
    {
        $payload = array_merge([
            'application_number' => 'IT-APP-' . strtoupper(bin2hex(random_bytes(4))),
            'adopter_id' => 0,
            'animal_id' => null,
            'status' => 'pending_review',
            'preferred_species' => null,
            'preferred_breed' => null,
            'preferred_age_min' => null,
            'preferred_age_max' => null,
            'preferred_size' => null,
            'preferred_gender' => null,
            'housing_type' => 'House',
            'housing_ownership' => 'Owned',
            'has_yard' => 1,
            'yard_size' => 'Medium',
            'num_adults' => 2,
            'num_children' => 0,
            'children_ages' => null,
            'existing_pets_description' => null,
            'previous_pet_experience' => 'Has cared for pets before.',
            'vet_reference_name' => null,
            'vet_reference_clinic' => null,
            'vet_reference_contact' => null,
            'valid_id_path' => 'uploads/adoptions/documents/test-valid-id.png',
            'digital_signature_path' => null,
            'agrees_to_policies' => 1,
            'agrees_to_home_visit' => 1,
            'agrees_to_return_policy' => 1,
            'created_by' => null,
            'updated_by' => null,
        ], $overrides);

        Database::execute(
            'INSERT INTO adoption_applications (
                application_number, adopter_id, animal_id, status, preferred_species, preferred_breed,
                preferred_age_min, preferred_age_max, preferred_size, preferred_gender, housing_type,
                housing_ownership, has_yard, yard_size, num_adults, num_children, children_ages,
                existing_pets_description, previous_pet_experience, vet_reference_name, vet_reference_clinic,
                vet_reference_contact, valid_id_path, digital_signature_path, agrees_to_policies,
                agrees_to_home_visit, agrees_to_return_policy, created_by, updated_by
             ) VALUES (
                :application_number, :adopter_id, :animal_id, :status, :preferred_species, :preferred_breed,
                :preferred_age_min, :preferred_age_max, :preferred_size, :preferred_gender, :housing_type,
                :housing_ownership, :has_yard, :yard_size, :num_adults, :num_children, :children_ages,
                :existing_pets_description, :previous_pet_experience, :vet_reference_name, :vet_reference_clinic,
                :vet_reference_contact, :valid_id_path, :digital_signature_path, :agrees_to_policies,
                :agrees_to_home_visit, :agrees_to_return_policy, :created_by, :updated_by
             )',
            $payload
        );

        $applicationId = (int) Database::lastInsertId();

        return Database::fetch('SELECT * FROM adoption_applications WHERE id = :id LIMIT 1', ['id' => $applicationId]) ?: [];
    }

    protected function createSeminar(array $overrides = []): array
    {
        $payload = array_merge([
            'title' => 'Integration Seminar ' . strtoupper(bin2hex(random_bytes(3))),
            'scheduled_date' => date('Y-m-d H:i:s', strtotime('+2 days')),
            'end_time' => date('Y-m-d H:i:s', strtotime('+2 days +2 hours')),
            'location' => 'Integration Hall',
            'capacity' => 20,
            'facilitator_id' => null,
            'description' => 'Integration seminar for adoption workflow tests.',
            'status' => 'scheduled',
            'created_by' => null,
        ], $overrides);

        Database::execute(
            'INSERT INTO adoption_seminars (
                title, scheduled_date, end_time, location, capacity, facilitator_id, description, status, created_by
             ) VALUES (
                :title, :scheduled_date, :end_time, :location, :capacity, :facilitator_id, :description, :status, :created_by
             )',
            $payload
        );

        $seminarId = (int) Database::lastInsertId();

        return Database::fetch('SELECT * FROM adoption_seminars WHERE id = :id LIMIT 1', ['id' => $seminarId]) ?: [];
    }

    protected function createInvoice(array $overrides = []): array
    {
        $payload = array_merge([
            'invoice_number' => 'IT-INV-' . strtoupper(bin2hex(random_bytes(4))),
            'payor_type' => 'adopter',
            'payor_user_id' => null,
            'payor_name' => 'Integration Payor',
            'payor_contact' => null,
            'payor_address' => null,
            'animal_id' => null,
            'application_id' => null,
            'subtotal' => 500.0,
            'tax_amount' => 0.0,
            'total_amount' => 500.0,
            'amount_paid' => 0.0,
            'payment_status' => 'unpaid',
            'issue_date' => date('Y-m-d'),
            'due_date' => date('Y-m-d', strtotime('+7 days')),
            'notes' => null,
            'terms' => null,
            'pdf_path' => null,
            'voided_at' => null,
            'voided_reason' => null,
            'created_by' => null,
            'updated_by' => null,
        ], $overrides);

        Database::execute(
            'INSERT INTO invoices (
                invoice_number, payor_type, payor_user_id, payor_name, payor_contact, payor_address,
                animal_id, application_id, subtotal, tax_amount, total_amount, amount_paid, payment_status,
                issue_date, due_date, notes, terms, pdf_path, voided_at, voided_reason, created_by, updated_by
             ) VALUES (
                :invoice_number, :payor_type, :payor_user_id, :payor_name, :payor_contact, :payor_address,
                :animal_id, :application_id, :subtotal, :tax_amount, :total_amount, :amount_paid, :payment_status,
                :issue_date, :due_date, :notes, :terms, :pdf_path, :voided_at, :voided_reason, :created_by, :updated_by
             )',
            $payload
        );

        $invoiceId = (int) Database::lastInsertId();

        return Database::fetch('SELECT * FROM invoices WHERE id = :id LIMIT 1', ['id' => $invoiceId]) ?: [];
    }
}
