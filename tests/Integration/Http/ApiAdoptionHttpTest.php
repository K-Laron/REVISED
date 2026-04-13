<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

require_once __DIR__ . '/HttpIntegrationTestCase.php';

use App\Core\Database;

final class ApiAdoptionHttpTest extends HttpIntegrationTestCase
{
    public function testPipelineStatsReturnsConsolidatedCounts(): void
    {
        $user = $this->createUser('super_admin');
        $this->authenticateUser($user);
        $_ENV['APP_PERFORMANCE_DEBUG'] = '1';

        $baseline = $this->dispatchJson('GET', '/api/adoptions/pipeline-stats');
        $adopter = $this->createUser('adopter');
        $application = $this->createApplication([
            'adopter_id' => $adopter['id'],
            'status' => 'seminar_completed',
        ]);
        $this->createSeminar([
            'status' => 'scheduled',
            'scheduled_date' => date('Y-m-d H:i:s', strtotime('+2 days')),
        ]);

        Database::execute(
            'INSERT INTO adoption_interviews (
                application_id, scheduled_date, interview_type, location, status, conducted_by
             ) VALUES (
                :application_id, :scheduled_date, :interview_type, :location, :status, :conducted_by
             )',
            [
                'application_id' => $application['id'],
                'scheduled_date' => date('Y-m-d H:i:s', strtotime('+1 day')),
                'interview_type' => 'in_person',
                'location' => 'Integration Room',
                'status' => 'scheduled',
                'conducted_by' => $user['id'],
            ]
        );

        $response = $this->dispatchJson('GET', '/api/adoptions/pipeline-stats');

        self::assertSame(200, $response['status']);
        self::assertSame(
            (int) ($baseline['json']['data']['ready_for_completion'] ?? 0) + 1,
            (int) ($response['json']['data']['ready_for_completion'] ?? 0)
        );
        self::assertSame(
            (int) ($baseline['json']['data']['upcoming_interviews'] ?? 0) + 1,
            (int) ($response['json']['data']['upcoming_interviews'] ?? 0)
        );
        self::assertSame(
            (int) ($baseline['json']['data']['upcoming_seminars'] ?? 0) + 1,
            (int) ($response['json']['data']['upcoming_seminars'] ?? 0)
        );
        self::assertLessThanOrEqual(2, (int) ($response['headers']['X-App-Query-Count'] ?? PHP_INT_MAX));
    }

    public function testSeminarsListIncludesAnimalIdentifierForAttendees(): void
    {
        $user = $this->createUser('super_admin');
        $this->authenticateUser($user);
        $adopter = $this->createUser('adopter');
        $animal = $this->createAnimal([
            'animal_id' => 'HTTP-SEMINAR-001',
        ]);
        $application = $this->createApplication([
            'adopter_id' => $adopter['id'],
            'animal_id' => $animal['id'],
            'status' => 'interview_completed',
        ]);
        $seminar = $this->createSeminar([
            'created_by' => (int) $user['id'],
            'facilitator_id' => (int) $user['id'],
        ]);

        Database::execute(
            'INSERT INTO seminar_attendees (
                seminar_id, application_id, attendance_status
             ) VALUES (
                :seminar_id, :application_id, :attendance_status
             )',
            [
                'seminar_id' => $seminar['id'],
                'application_id' => $application['id'],
                'attendance_status' => 'registered',
            ]
        );

        $response = $this->dispatchJson('GET', '/api/adoptions/seminars');

        self::assertSame(200, $response['status']);
        self::assertTrue($response['json']['success']);

        $seminars = is_array($response['json']['data'] ?? null) ? $response['json']['data'] : [];
        $matchingSeminar = array_values(array_filter(
            $seminars,
            static fn (array $item): bool => (int) ($item['id'] ?? 0) === (int) $seminar['id']
        ));

        self::assertCount(1, $matchingSeminar);
        self::assertSame('HTTP-SEMINAR-001', $matchingSeminar[0]['attendees'][0]['animal_code'] ?? null);
    }
}
