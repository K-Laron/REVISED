<?php

declare(strict_types=1);

namespace App\Services\Search\Providers;

use App\Core\Database;
use App\Services\Search\AbstractSearchProvider;

final class AdoptionsSearchProvider extends AbstractSearchProvider
{
    public function secondaryFilters(): array
    {
        return [
            'adoption_status' => [
                'label' => 'Adoption Status',
                'options' => [
                    ['value' => 'pending_review', 'label' => 'Pending Review'],
                    ['value' => 'interview_scheduled', 'label' => 'Interview Scheduled'],
                    ['value' => 'completed', 'label' => 'Completed'],
                    ['value' => 'rejected', 'label' => 'Rejected'],
                ],
            ],
        ];
    }

    public function legacyStatusAliases(): array
    {
        return [
            'adoption_pending' => ['key' => 'adoption_status', 'value' => 'pending_review'],
            'adoption_completed' => ['key' => 'adoption_status', 'value' => 'completed'],
            'adoption_rejected' => ['key' => 'adoption_status', 'value' => 'rejected'],
        ];
    }

    public function key(): string
    {
        return 'adoptions';
    }

    public function label(): string
    {
        return 'Adoptions';
    }

    public function permission(): string
    {
        return 'adoptions.read';
    }

    public function search(string $term, int $limit, array $filters): array
    {
        $bindings = $this->likeBindings($term, 6);
        $filterClause = $this->standardFilterClause((string) ($filters['adoption_status'] ?? ''), $filters, 'aa.status', 'aa.created_at', 'adoptions');
        $rows = Database::fetchAll(
            "SELECT aa.id, aa.application_number, aa.status,
                    CONCAT(u.first_name, ' ', u.last_name) AS adopter_name,
                    a.animal_id AS animal_code, a.name AS animal_name
             FROM adoption_applications aa
             INNER JOIN users u ON u.id = aa.adopter_id
             LEFT JOIN animals a ON a.id = aa.animal_id
             WHERE aa.is_deleted = 0
               AND (
                    aa.application_number LIKE :search_1
                    OR CONCAT(u.first_name, ' ', u.last_name) LIKE :search_2
                    OR u.email LIKE :search_3
                    OR u.username LIKE :search_4
                    OR a.animal_id LIKE :search_5
                    OR a.name LIKE :search_6
               )"
             . $filterClause['sql'] . "
             ORDER BY aa.created_at DESC, aa.id DESC
             LIMIT " . ($limit + 1),
            $bindings + $filterClause['bindings']
        );
        $preview = $this->previewResult(
            $rows,
            $limit,
            static fn (): int => (int) ((Database::fetch(
                "SELECT COUNT(*) AS aggregate
                 FROM adoption_applications aa
                 INNER JOIN users u ON u.id = aa.adopter_id
                 LEFT JOIN animals a ON a.id = aa.animal_id
                 WHERE aa.is_deleted = 0
                   AND (
                        aa.application_number LIKE :search_1
                        OR CONCAT(u.first_name, ' ', u.last_name) LIKE :search_2
                        OR u.email LIKE :search_3
                        OR u.username LIKE :search_4
                        OR a.animal_id LIKE :search_5
                        OR a.name LIKE :search_6
                   )"
                   . $filterClause['sql'],
                $bindings + $filterClause['bindings']
            )['aggregate'] ?? 0))
        );

        return $this->section(
            'adoptions',
            'Adoptions',
            '/adoptions',
            $preview['count'],
            array_map(static fn (array $item): array => [
                'title' => (string) ($item['application_number'] ?? ''),
                'subtitle' => trim((string) (($item['adopter_name'] ?? '') . (($item['animal_name'] ?? '') !== '' ? ' • ' . $item['animal_name'] : ''))),
                'meta' => (string) ($item['animal_code'] ?? ''),
                'badge' => (string) ($item['status'] ?? ''),
                'href' => '/adoptions/' . (int) $item['id'],
            ], $preview['items'])
        );
    }
}
