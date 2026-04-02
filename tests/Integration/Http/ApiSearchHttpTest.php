<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

require_once __DIR__ . '/HttpIntegrationTestCase.php';

final class ApiSearchHttpTest extends HttpIntegrationTestCase
{
    public function testGlobalSearchReturnsAnimalsSectionForMatchingRecords(): void
    {
        $user = $this->createUser('super_admin');
        $this->authenticateUser($user);
        $this->createAnimal([
            'name' => 'Search Fixture Buddy',
            'animal_id' => 'SEARCH-BUDDY',
        ]);

        $response = $this->dispatchJson('GET', '/api/search/global', [], [
            'q' => 'Search Fixture',
            'modules' => ['animals'],
            'per_section' => 3,
        ]);

        self::assertSame(200, $response['status']);
        self::assertTrue($response['json']['success']);
        self::assertSame('Search Fixture', $response['json']['data']['query']);
        self::assertSame('animals', $response['json']['data']['sections'][0]['key']);
        self::assertGreaterThanOrEqual(1, $response['json']['data']['sections'][0]['count']);
        self::assertStringStartsWith('/animals/', (string) $response['json']['data']['sections'][0]['items'][0]['href']);
    }

    public function testGlobalSearchAcrossAllAccessibleModulesDoesNotError(): void
    {
        $user = $this->createUser('super_admin');
        $this->authenticateUser($user);

        $response = $this->dispatchJson('GET', '/api/search/global', [], [
            'q' => 'Catarman',
            'per_section' => 5,
        ]);

        self::assertSame(200, $response['status']);
        self::assertTrue($response['json']['success']);
        self::assertSame('Catarman', $response['json']['data']['query']);
    }
}
