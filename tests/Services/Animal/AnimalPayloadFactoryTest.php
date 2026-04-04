<?php

declare(strict_types=1);

namespace Tests\Services\Animal;

use App\Services\Animal\AnimalPayloadFactory;
use PHPUnit\Framework\TestCase;

final class AnimalPayloadFactoryTest extends TestCase
{
    public function testCreatePayloadClearsConditionalFieldsThatDoNotApply(): void
    {
        $payload = (new AnimalPayloadFactory())->build([
            'species' => 'Dog',
            'gender' => 'Male',
            'size' => 'Medium',
            'intake_type' => 'Born in Shelter',
            'intake_date' => '2026-04-04T09:30',
            'condition_at_intake' => 'Healthy',
            'temperament' => 'Friendly',
            'location_found' => 'Market road',
            'surrender_reason' => 'No longer wanted',
            'authority_name' => 'Barangay Captain',
            'authority_position' => 'Captain',
            'authority_contact' => '09171234567',
            'brought_by_name' => 'Kenneth',
            'brought_by_contact' => '09171234568',
            'brought_by_address' => 'Catarman',
        ], 12);

        self::assertSame('Available', $payload['status']);
        self::assertSame('Initial intake', $payload['status_reason']);
        self::assertSame('2026-04-04 09:30:00', $payload['intake_date']);
        self::assertNull($payload['location_found']);
        self::assertNull($payload['surrender_reason']);
        self::assertNull($payload['authority_name']);
        self::assertNull($payload['authority_position']);
        self::assertNull($payload['authority_contact']);
        self::assertNull($payload['brought_by_name']);
        self::assertNull($payload['brought_by_contact']);
        self::assertNull($payload['brought_by_address']);
        self::assertSame(12, $payload['created_by']);
        self::assertSame(12, $payload['updated_by']);
    }

    public function testUpdatePayloadKeepsConditionalFieldsThatStillApply(): void
    {
        $payload = (new AnimalPayloadFactory())->build([
            'species' => 'Dog',
            'gender' => 'Female',
            'size' => 'Small',
            'intake_type' => 'Owner Surrender',
            'intake_date' => '2026-04-04 09:30:00',
            'condition_at_intake' => 'Healthy',
            'temperament' => 'Friendly',
            'surrender_reason' => 'Moving away',
            'brought_by_name' => 'Kenneth',
            'brought_by_contact' => '09171234567',
            'brought_by_address' => 'Catarman',
            'status' => 'Under Medical Care',
            'status_reason' => 'Observation',
            'status_changed_at' => '2026-04-04 10:00:00',
        ], 9, false);

        self::assertNull($payload['created_by']);
        self::assertSame(9, $payload['updated_by']);
        self::assertSame('Under Medical Care', $payload['status']);
        self::assertSame('Observation', $payload['status_reason']);
        self::assertSame('2026-04-04 10:00:00', $payload['status_changed_at']);
        self::assertSame('Moving away', $payload['surrender_reason']);
        self::assertSame('Kenneth', $payload['brought_by_name']);
        self::assertSame('09171234567', $payload['brought_by_contact']);
        self::assertSame('Catarman', $payload['brought_by_address']);
        self::assertNull($payload['location_found']);
        self::assertNull($payload['authority_name']);
    }
}
