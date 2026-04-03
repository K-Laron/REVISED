<?php

declare(strict_types=1);

namespace App\Services\Search\Providers;

use App\Core\Database;
use App\Services\Search\AbstractSearchProvider;

final class UsersSearchProvider extends AbstractSearchProvider
{
    public function secondaryFilters(): array
    {
        return [
            'users_status' => [
                'label' => 'User State',
                'options' => [
                    ['value' => 'active', 'label' => 'Active'],
                    ['value' => 'inactive', 'label' => 'Inactive'],
                ],
            ],
        ];
    }

    public function legacyStatusAliases(): array
    {
        return [
            'user_active' => ['key' => 'users_status', 'value' => 'active'],
            'user_inactive' => ['key' => 'users_status', 'value' => 'inactive'],
        ];
    }

    public function key(): string
    {
        return 'users';
    }

    public function label(): string
    {
        return 'Users';
    }

    public function permission(): string
    {
        return 'users.read';
    }

    public function search(string $term, int $limit, array $filters): array
    {
        $bindings = $this->likeBindings($term, 4);
        $filterClause = $this->userFilterClause((string) ($filters['users_status'] ?? ''), $filters);
        $rows = Database::fetchAll(
            "SELECT u.id, u.username, u.email, u.phone, CONCAT(u.first_name, ' ', u.last_name) AS full_name, r.display_name AS role_display_name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE u.is_deleted = 0
               AND (
                    CONCAT(u.first_name, ' ', u.last_name) LIKE :search_1
                    OR u.username LIKE :search_2
                    OR u.email LIKE :search_3
                    OR u.phone LIKE :search_4
               )"
               . $filterClause['sql'] . "
             ORDER BY u.first_name ASC, u.last_name ASC
             LIMIT " . ($limit + 1),
            $bindings + $filterClause['bindings']
        );
        $preview = $this->previewResult(
            $rows,
            $limit,
            static fn (): int => (int) ((Database::fetch(
                "SELECT COUNT(*) AS aggregate
                 FROM users u
                 INNER JOIN roles r ON r.id = u.role_id
                 WHERE u.is_deleted = 0
                   AND (
                        CONCAT(u.first_name, ' ', u.last_name) LIKE :search_1
                        OR u.username LIKE :search_2
                        OR u.email LIKE :search_3
                        OR u.phone LIKE :search_4
                   )"
                   . $filterClause['sql'],
                $bindings + $filterClause['bindings']
            )['aggregate'] ?? 0))
        );

        return $this->section(
            'users',
            'Users',
            '/users',
            $preview['count'],
            array_map(static fn (array $item): array => [
                'title' => (string) ($item['full_name'] ?? ''),
                'subtitle' => trim((string) (($item['username'] ?? '') !== '' ? '@' . $item['username'] : ($item['email'] ?? ''))),
                'meta' => trim((string) (($item['email'] ?? '') . (($item['phone'] ?? '') !== '' ? ' • ' . $item['phone'] : ''))),
                'badge' => (string) ($item['role_display_name'] ?? ''),
                'href' => '/users/' . (int) $item['id'],
            ], $preview['items'])
        );
    }
}
