<?php

declare(strict_types=1);

namespace App\Services\Search\Providers;

use App\Core\Database;
use App\Services\Search\AbstractSearchProvider;

final class AnimalsSearchProvider extends AbstractSearchProvider
{
    public function secondaryFilters(): array
    {
        return [
            'animals_status' => [
                'label' => 'Animal Status',
                'options' => [
                    ['value' => 'Available', 'label' => 'Available'],
                    ['value' => 'Adopted', 'label' => 'Adopted'],
                    ['value' => 'Under Medical Care', 'label' => 'Under Medical Care'],
                    ['value' => 'Quarantine', 'label' => 'Quarantine'],
                ],
            ],
        ];
    }

    public function legacyStatusAliases(): array
    {
        return [
            'animal_available' => ['key' => 'animals_status', 'value' => 'Available'],
            'animal_adopted' => ['key' => 'animals_status', 'value' => 'Adopted'],
            'animal_medical' => ['key' => 'animals_status', 'value' => 'Under Medical Care'],
            'animal_quarantine' => ['key' => 'animals_status', 'value' => 'Quarantine'],
        ];
    }

    public function key(): string
    {
        return 'animals';
    }

    public function label(): string
    {
        return 'Animals';
    }

    public function permission(): string
    {
        return 'animals.read';
    }

    public function search(string $term, int $limit, array $filters): array
    {
        $bindings = $this->likeBindings($term, 2);
        $filterClause = $this->standardFilterClause((string) ($filters['animals_status'] ?? ''), $filters, 'a.status', 'a.intake_date', 'animals');
        $rows = $this->db->fetchAll(
            "SELECT a.id, a.animal_id, a.name, a.species, a.status
             FROM animals a
             WHERE a.is_deleted = 0
               AND (a.animal_id LIKE :search_1 OR a.name LIKE :search_2)"
               . $filterClause['sql'] . "
             ORDER BY a.created_at DESC, a.id DESC
             LIMIT " . ($limit + 1),
            $bindings + $filterClause['bindings']
        );
        $preview = $this->previewResult(
            $rows,
            $limit,
            fn (): int => (int) (($this->db->fetch(
                "SELECT COUNT(*) AS aggregate
                 FROM animals a
                 WHERE a.is_deleted = 0
                   AND (a.animal_id LIKE :search_1 OR a.name LIKE :search_2)"
                   . $filterClause['sql'],
                $bindings + $filterClause['bindings']
            )['aggregate'] ?? 0))
        );

        return $this->section(
            'animals',
            'Animals',
            '/animals',
            $preview['count'],
            array_map(static fn (array $item): array => [
                'title' => trim((string) ($item['name'] ?: $item['animal_id'])),
                'subtitle' => trim((string) $item['animal_id']),
                'meta' => trim((string) (($item['species'] ?? '') . ' • ' . ($item['status'] ?? ''))),
                'badge' => (string) ($item['status'] ?? ''),
                'href' => '/animals/' . (int) $item['id'],
            ], $preview['items'])
        );
    }
}
