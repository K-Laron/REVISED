<?php

declare(strict_types=1);

namespace Tests\Integration\Search;

use App\Services\Search\Providers\AnimalsSearchProvider;
use App\Support\Performance\PerformanceProbe;
use Tests\Integration\DatabaseIntegrationTestCase;

require_once __DIR__ . '/../DatabaseIntegrationTestCase.php';

final class AnimalsSearchProviderTest extends DatabaseIntegrationTestCase
{
    protected function tearDown(): void
    {
        PerformanceProbe::reset();
        parent::tearDown();
    }

    public function testSearchSkipsTheCountQueryWhenThePreviewDoesNotOverflow(): void
    {
        $this->createAnimal([
            'animal_id' => 'PERF-ANIMAL-001',
            'name' => 'Performance Search Buddy',
        ]);

        PerformanceProbe::forceEnabled(true);
        PerformanceProbe::startRequest('CLI', '/tests/search/animals');

        $section = (new AnimalsSearchProvider())->search('Performance Search', 5, []);
        $summary = PerformanceProbe::finishRequest();

        self::assertSame(1, $section['count']);
        self::assertCount(1, $section['items']);
        self::assertSame(1, $summary['query_count']);
    }
}
