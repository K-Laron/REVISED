<?php

declare(strict_types=1);

namespace Tests\Services\Animal;

use App\Models\AnimalPhoto;
use App\Services\Animal\AnimalPhotoManager;
use PHPUnit\Framework\TestCase;

final class AnimalPhotoManagerTest extends TestCase
{
    private string $publicRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->publicRoot = sys_get_temp_dir() . '/animal-photo-manager-' . bin2hex(random_bytes(6));
        mkdir($this->publicRoot, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->publicRoot);

        parent::tearDown();
    }

    public function testUploadCopiesFilesIntoAnimalDirectoryAndCreatesPrimaryPhoto(): void
    {
        $repository = new class extends AnimalPhoto {
            public array $records = [];

            public function listByAnimal(int $animalId): array
            {
                return array_values(array_filter(
                    $this->records,
                    static fn (array $record): bool => (int) $record['animal_id'] === $animalId
                ));
            }

            public function create(array $data): int
            {
                $data['id'] = count($this->records) + 1;
                $this->records[] = $data;

                return $data['id'];
            }
        };

        $source = tempnam(sys_get_temp_dir(), 'animal-photo-');
        file_put_contents($source, 'fake-image');

        $manager = new AnimalPhotoManager($repository, $this->publicRoot, null);
        $manager->upload(42, [
            'name' => 'example.png',
            'type' => 'image/png',
            'tmp_name' => $source,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($source),
        ], 9);

        self::assertCount(1, $repository->records);
        self::assertSame(42, $repository->records[0]['animal_id']);
        self::assertSame(1, $repository->records[0]['is_primary']);
        self::assertStringStartsWith('uploads/animals/42/animal-photo-', $repository->records[0]['file_path']);
        self::assertFileExists($this->publicRoot . '/' . $repository->records[0]['file_path']);

        unlink($source);
    }

    public function testDeleteRemovesThePhysicalFileAndRepositoryRow(): void
    {
        $relativePath = 'uploads/animals/42/example.png';
        $absolutePath = $this->publicRoot . '/' . $relativePath;
        mkdir(dirname($absolutePath), 0777, true);
        file_put_contents($absolutePath, 'fake-image');

        $repository = new class($relativePath) extends AnimalPhoto {
            public int $deletedId = 0;

            public function __construct(private readonly string $relativePath)
            {
            }

            public function find(int $animalId, int $photoId): array|false
            {
                return [
                    'id' => $photoId,
                    'animal_id' => $animalId,
                    'file_path' => $this->relativePath,
                ];
            }

            public function delete(int $photoId): void
            {
                $this->deletedId = $photoId;
            }
        };

        $manager = new AnimalPhotoManager($repository, $this->publicRoot, null);
        $manager->delete(42, 7);

        self::assertSame(7, $repository->deletedId);
        self::assertFileDoesNotExist($absolutePath);
    }

    public function testUploadUsesPhotoCountWithoutLoadingFullExistingList(): void
    {
        $repository = new class extends AnimalPhoto {
            public array $records = [];

            public function countByAnimal(int $animalId): int
            {
                return 2;
            }

            public function listByAnimal(int $animalId): array
            {
                throw new \RuntimeException('Full photo list should not be loaded.');
            }

            public function create(array $data): int
            {
                $data['id'] = count($this->records) + 1;
                $this->records[] = $data;

                return $data['id'];
            }
        };

        $source = tempnam(sys_get_temp_dir(), 'animal-photo-');
        file_put_contents($source, 'fake-image');

        $manager = new AnimalPhotoManager($repository, $this->publicRoot, null);
        $manager->upload(42, [
            'name' => 'example.png',
            'type' => 'image/png',
            'tmp_name' => $source,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($source),
        ], 9);

        self::assertCount(1, $repository->records);
        self::assertSame(0, $repository->records[0]['is_primary']);
        self::assertSame(2, $repository->records[0]['sort_order']);

        unlink($source);
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $target = $path . '/' . $item;
            if (is_dir($target)) {
                $this->removeDirectory($target);
                continue;
            }

            @unlink($target);
        }

        @rmdir($path);
    }
}
