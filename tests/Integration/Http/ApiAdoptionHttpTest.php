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
}
