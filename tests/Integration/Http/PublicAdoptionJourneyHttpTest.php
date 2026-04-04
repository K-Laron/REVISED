<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

require_once __DIR__ . '/HttpIntegrationTestCase.php';

final class PublicAdoptionJourneyHttpTest extends HttpIntegrationTestCase
{
    public function testAnimalsListingLoadsForGuests(): void
    {
        $response = $this->dispatchJson('GET', '/adopt/animals', [], [], ['HTTP_ACCEPT' => 'text/html']);

        self::assertSame(200, $response['status']);
        self::assertStringContainsString('Available animals', $response['content']);
        self::assertStringContainsString('portal-animal-results', $response['content']);
    }

    public function testApplyPageLoadsForAuthenticatedAdopter(): void
    {
        $adopter = $this->createUser('adopter');
        $this->authenticateUser($adopter);

        $response = $this->dispatchJson('GET', '/adopt/apply', [], [], ['HTTP_ACCEPT' => 'text/html']);

        self::assertSame(200, $response['status']);
        self::assertStringContainsString('My Adoption Application', $response['content']);
        self::assertStringContainsString('id="portal-apply-form"', $response['content']);
        self::assertStringContainsString('name="animal_id"', $response['content']);
    }
}
