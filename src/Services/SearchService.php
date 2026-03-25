<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

class SearchService
{
    private const MODULES = [
        'animals' => ['label' => 'Animals', 'permission' => 'animals.read', 'method' => 'animals'],
        'medical' => ['label' => 'Medical Records', 'permission' => 'medical.read', 'method' => 'medical'],
        'adoptions' => ['label' => 'Adoptions', 'permission' => 'adoptions.read', 'method' => 'adoptions'],
        'billing' => ['label' => 'Invoices', 'permission' => 'billing.read', 'method' => 'billing'],
        'inventory' => ['label' => 'Inventory', 'permission' => 'inventory.read', 'method' => 'inventory'],
        'users' => ['label' => 'Users', 'permission' => 'users.read', 'method' => 'users'],
    ];

    private const SECONDARY_FILTERS = [
        'animals_status' => [
            'module' => 'animals',
            'label' => 'Animal Status',
            'options' => [
                ['value' => 'Available', 'label' => 'Available'],
                ['value' => 'Adopted', 'label' => 'Adopted'],
                ['value' => 'Under Medical Care', 'label' => 'Under Medical Care'],
                ['value' => 'Quarantine', 'label' => 'Quarantine'],
            ],
        ],
        'medical_type' => [
            'module' => 'medical',
            'label' => 'Procedure Type',
            'options' => [
                ['value' => 'vaccination', 'label' => 'Vaccination'],
                ['value' => 'treatment', 'label' => 'Treatment'],
                ['value' => 'surgery', 'label' => 'Surgery'],
                ['value' => 'examination', 'label' => 'Examination'],
            ],
        ],
        'adoption_status' => [
            'module' => 'adoptions',
            'label' => 'Adoption Status',
            'options' => [
                ['value' => 'pending_review', 'label' => 'Pending Review'],
                ['value' => 'interview_scheduled', 'label' => 'Interview Scheduled'],
                ['value' => 'completed', 'label' => 'Completed'],
                ['value' => 'rejected', 'label' => 'Rejected'],
            ],
        ],
        'billing_status' => [
            'module' => 'billing',
            'label' => 'Payment Status',
            'options' => [
                ['value' => 'unpaid', 'label' => 'Unpaid'],
                ['value' => 'partial', 'label' => 'Partial'],
                ['value' => 'paid', 'label' => 'Paid'],
                ['value' => 'void', 'label' => 'Void'],
            ],
        ],
        'inventory_status' => [
            'module' => 'inventory',
            'label' => 'Inventory State',
            'options' => [
                ['value' => 'low_stock', 'label' => 'Low Stock'],
                ['value' => 'expiring', 'label' => 'Expiring Soon'],
                ['value' => 'active', 'label' => 'Active'],
                ['value' => 'inactive', 'label' => 'Inactive'],
            ],
        ],
        'users_status' => [
            'module' => 'users',
            'label' => 'User State',
            'options' => [
                ['value' => 'active', 'label' => 'Active'],
                ['value' => 'inactive', 'label' => 'Inactive'],
            ],
        ],
    ];

    private const STATUS_FILTERS = [
        'animal_available' => ['key' => 'animals_status', 'value' => 'Available'],
        'animal_adopted' => ['key' => 'animals_status', 'value' => 'Adopted'],
        'animal_medical' => ['key' => 'animals_status', 'value' => 'Under Medical Care'],
        'animal_quarantine' => ['key' => 'animals_status', 'value' => 'Quarantine'],
        'medical_vaccination' => ['key' => 'medical_type', 'value' => 'vaccination'],
        'medical_treatment' => ['key' => 'medical_type', 'value' => 'treatment'],
        'medical_surgery' => ['key' => 'medical_type', 'value' => 'surgery'],
        'medical_examination' => ['key' => 'medical_type', 'value' => 'examination'],
        'adoption_pending' => ['key' => 'adoption_status', 'value' => 'pending_review'],
        'adoption_completed' => ['key' => 'adoption_status', 'value' => 'completed'],
        'adoption_rejected' => ['key' => 'adoption_status', 'value' => 'rejected'],
        'billing_unpaid' => ['key' => 'billing_status', 'value' => 'unpaid'],
        'billing_partial' => ['key' => 'billing_status', 'value' => 'partial'],
        'billing_paid' => ['key' => 'billing_status', 'value' => 'paid'],
        'inventory_low_stock' => ['key' => 'inventory_status', 'value' => 'low_stock'],
        'inventory_expiring' => ['key' => 'inventory_status', 'value' => 'expiring'],
        'inventory_active' => ['key' => 'inventory_status', 'value' => 'active'],
        'inventory_inactive' => ['key' => 'inventory_status', 'value' => 'inactive'],
        'user_active' => ['key' => 'users_status', 'value' => 'active'],
        'user_inactive' => ['key' => 'users_status', 'value' => 'inactive'],
    ];

    public function search(string $query, array $user, array $filters = []): array
    {
        $term = trim($query);
        $normalizedFilters = $this->normalizedFilters($filters);
        $perSection = $normalizedFilters['per_section'];
        $availableModules = $this->availableModules($user);
        $selectedModules = $this->selectedModules($normalizedFilters['modules'], $availableModules);

        if (mb_strlen($term) < 2) {
            return [
                'query' => $term,
                'total_results' => 0,
                'sections' => [],
                'applied_filters' => $this->appliedFilters($selectedModules, $normalizedFilters),
            ];
        }

        $sections = [];
        foreach ($selectedModules as $moduleKey) {
            $definition = self::MODULES[$moduleKey] ?? null;
            if ($definition === null) {
                continue;
            }

            $method = $definition['method'];
            if (method_exists($this, $method)) {
                $sections[] = $this->{$method}($term, $perSection, $normalizedFilters);
            }
        }

        $sections = array_values(array_filter($sections, static fn (array $section): bool => $section['count'] > 0));
        $totalResults = array_sum(array_column($sections, 'count'));

        return [
            'query' => $term,
            'total_results' => $totalResults,
            'sections' => $sections,
            'applied_filters' => $this->appliedFilters($selectedModules, $normalizedFilters),
        ];
    }

    public function availableModules(array $user): array
    {
        $modules = [];

        foreach (self::MODULES as $key => $definition) {
            if (!$this->canAccess($user, $definition['permission'])) {
                continue;
            }

            $modules[] = [
                'key' => $key,
                'label' => $definition['label'],
            ];
        }

        return $modules;
    }

    public function availableSecondaryFilters(array $user): array
    {
        $filters = [];

        foreach (self::SECONDARY_FILTERS as $key => $definition) {
            $module = (string) ($definition['module'] ?? '');
            $permission = self::MODULES[$module]['permission'] ?? '';
            if ($permission === '' || !$this->canAccess($user, $permission)) {
                continue;
            }

            $filters[$key] = [
                'key' => $key,
                'module' => $module,
                'label' => (string) ($definition['label'] ?? $key),
                'options' => $definition['options'] ?? [],
            ];
        }

        return $filters;
    }

    private function animals(string $term, int $limit, array $filters): array
    {
        $bindings = $this->likeBindings($term, 2);
        $filterClause = $this->standardFilterClause((string) ($filters['animals_status'] ?? ''), $filters, 'a.status', 'a.intake_date', 'animals');
        $count = Database::fetch(
            "SELECT COUNT(*) AS aggregate
             FROM animals a
             WHERE a.is_deleted = 0
               AND (a.animal_id LIKE :search_1 OR a.name LIKE :search_2)"
               . $filterClause['sql'],
            $bindings + $filterClause['bindings']
        );

        $items = Database::fetchAll(
            "SELECT a.id, a.animal_id, a.name, a.species, a.status
             FROM animals a
             WHERE a.is_deleted = 0
               AND (a.animal_id LIKE :search_1 OR a.name LIKE :search_2)"
               . $filterClause['sql'] . "
             ORDER BY a.created_at DESC, a.id DESC
             LIMIT {$limit}",
            $bindings + $filterClause['bindings']
        );

        return $this->section(
            'animals',
            'Animals',
            '/animals',
            (int) ($count['aggregate'] ?? 0),
            array_map(static fn (array $item): array => [
                'title' => trim((string) ($item['name'] ?: $item['animal_id'])),
                'subtitle' => trim((string) $item['animal_id']),
                'meta' => trim((string) (($item['species'] ?? '') . ' • ' . ($item['status'] ?? ''))),
                'badge' => (string) ($item['status'] ?? ''),
                'href' => '/animals/' . (int) $item['id'],
            ], $items)
        );
    }

    private function medical(string $term, int $limit, array $filters): array
    {
        $bindings = $this->likeBindings($term, 3);
        $filterClause = $this->standardFilterClause((string) ($filters['medical_type'] ?? ''), $filters, 'mr.procedure_type', 'mr.record_date', 'medical');
        $count = Database::fetch(
            "SELECT COUNT(*) AS aggregate
             FROM medical_records mr
             INNER JOIN animals a ON a.id = mr.animal_id
             WHERE mr.is_deleted = 0
               AND (a.animal_id LIKE :search_1 OR a.name LIKE :search_2 OR mr.general_notes LIKE :search_3)"
               . $filterClause['sql'],
            $bindings + $filterClause['bindings']
        );

        $items = Database::fetchAll(
            "SELECT mr.id, mr.procedure_type, mr.record_date, a.id AS animal_id, a.animal_id AS animal_code, a.name AS animal_name
             FROM medical_records mr
             INNER JOIN animals a ON a.id = mr.animal_id
             WHERE mr.is_deleted = 0
               AND (a.animal_id LIKE :search_1 OR a.name LIKE :search_2 OR mr.general_notes LIKE :search_3)"
               . $filterClause['sql'] . "
             ORDER BY mr.record_date DESC, mr.id DESC
             LIMIT {$limit}",
            $bindings + $filterClause['bindings']
        );

        return $this->section(
            'medical',
            'Medical Records',
            '/medical',
            (int) ($count['aggregate'] ?? 0),
            array_map(static fn (array $item): array => [
                'title' => (string) ($item['animal_name'] ?: $item['animal_code']),
                'subtitle' => trim((string) (($item['animal_code'] ?? '') . ' • ' . ucfirst((string) $item['procedure_type']))),
                'meta' => (string) ($item['record_date'] ?? ''),
                'badge' => ucfirst((string) ($item['procedure_type'] ?? '')),
                'href' => '/medical/' . (int) $item['id'],
            ], $items)
        );
    }

    private function adoptions(string $term, int $limit, array $filters): array
    {
        $bindings = $this->likeBindings($term, 5);
        $filterClause = $this->standardFilterClause((string) ($filters['adoption_status'] ?? ''), $filters, 'aa.status', 'aa.created_at', 'adoptions');
        $count = Database::fetch(
            "SELECT COUNT(*) AS aggregate
             FROM adoption_applications aa
             INNER JOIN users u ON u.id = aa.adopter_id
             LEFT JOIN animals a ON a.id = aa.animal_id
             WHERE aa.is_deleted = 0
               AND (
                    aa.application_number LIKE :search_1
                    OR CONCAT(u.first_name, ' ', u.last_name) LIKE :search_2
                    OR u.email LIKE :search_3
                    OR u.username LIKE :search_3
                    OR a.animal_id LIKE :search_4
                    OR a.name LIKE :search_5
               )"
               . $filterClause['sql'],
            $bindings + $filterClause['bindings']
        );

        $items = Database::fetchAll(
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
                    OR u.username LIKE :search_3
                    OR a.animal_id LIKE :search_4
                    OR a.name LIKE :search_5
               )"
             . $filterClause['sql'] . "
             ORDER BY aa.created_at DESC, aa.id DESC
             LIMIT {$limit}",
            $bindings + $filterClause['bindings']
        );

        return $this->section(
            'adoptions',
            'Adoptions',
            '/adoptions',
            (int) ($count['aggregate'] ?? 0),
            array_map(static fn (array $item): array => [
                'title' => (string) ($item['application_number'] ?? ''),
                'subtitle' => trim((string) (($item['adopter_name'] ?? '') . (($item['animal_name'] ?? '') !== '' ? ' • ' . $item['animal_name'] : ''))),
                'meta' => (string) ($item['animal_code'] ?? ''),
                'badge' => (string) ($item['status'] ?? ''),
                'href' => '/adoptions/' . (int) $item['id'],
            ], $items)
        );
    }

    private function billing(string $term, int $limit, array $filters): array
    {
        $bindings = $this->likeBindings($term, 2);
        $filterClause = $this->standardFilterClause((string) ($filters['billing_status'] ?? ''), $filters, 'i.payment_status', 'i.issue_date', 'billing');
        $count = Database::fetch(
            "SELECT COUNT(*) AS aggregate
             FROM invoices i
             WHERE i.is_deleted = 0
               AND (i.invoice_number LIKE :search_1 OR i.payor_name LIKE :search_2)"
               . $filterClause['sql'],
            $bindings + $filterClause['bindings']
        );

        $items = Database::fetchAll(
            "SELECT i.id, i.invoice_number, i.payor_name, i.payment_status, i.total_amount
             FROM invoices i
             WHERE i.is_deleted = 0
               AND (i.invoice_number LIKE :search_1 OR i.payor_name LIKE :search_2)"
               . $filterClause['sql'] . "
             ORDER BY i.created_at DESC, i.id DESC
             LIMIT {$limit}",
            $bindings + $filterClause['bindings']
        );

        return $this->section(
            'billing',
            'Invoices',
            '/billing',
            (int) ($count['aggregate'] ?? 0),
            array_map(static fn (array $item): array => [
                'title' => (string) ($item['invoice_number'] ?? ''),
                'subtitle' => (string) ($item['payor_name'] ?? ''),
                'meta' => 'PHP ' . number_format((float) ($item['total_amount'] ?? 0), 2),
                'badge' => (string) ($item['payment_status'] ?? ''),
                'href' => '/billing/invoices/' . (int) $item['id'],
            ], $items)
        );
    }

    private function inventory(string $term, int $limit, array $filters): array
    {
        $bindings = $this->likeBindings($term, 2);
        $filterClause = $this->inventoryFilterClause((string) ($filters['inventory_status'] ?? ''), $filters);
        $count = Database::fetch(
            "SELECT COUNT(*) AS aggregate
             FROM inventory_items ii
             INNER JOIN inventory_categories ic ON ic.id = ii.category_id
             WHERE ii.is_deleted = 0
               AND (ii.sku LIKE :search_1 OR ii.name LIKE :search_2)"
               . $filterClause['sql'],
            $bindings + $filterClause['bindings']
        );

        $items = Database::fetchAll(
            "SELECT ii.id, ii.sku, ii.name, ii.quantity_on_hand, ii.reorder_level, ii.expiry_date, ii.is_active, ic.name AS category_name
             FROM inventory_items ii
             INNER JOIN inventory_categories ic ON ic.id = ii.category_id
             WHERE ii.is_deleted = 0
               AND (ii.sku LIKE :search_1 OR ii.name LIKE :search_2)"
               . $filterClause['sql'] . "
             ORDER BY ii.name ASC
             LIMIT {$limit}",
            $bindings + $filterClause['bindings']
        );

        return $this->section(
            'inventory',
            'Inventory',
            '/inventory',
            (int) ($count['aggregate'] ?? 0),
            array_map(static fn (array $item): array => [
                'title' => (string) ($item['name'] ?? ''),
                'subtitle' => trim((string) (($item['sku'] ?? '') . ' • ' . ($item['category_name'] ?? ''))),
                'meta' => 'On hand: ' . (int) ($item['quantity_on_hand'] ?? 0),
                'badge' => self::inventoryBadge($item),
                'href' => '/inventory/' . (int) $item['id'],
            ], $items)
        );
    }

    private function users(string $term, int $limit, array $filters): array
    {
        $bindings = $this->likeBindings($term, 4);
        $filterClause = $this->userFilterClause((string) ($filters['users_status'] ?? ''), $filters);
        $count = Database::fetch(
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
        );

        $items = Database::fetchAll(
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
             LIMIT {$limit}",
            $bindings + $filterClause['bindings']
        );

        return $this->section(
            'users',
            'Users',
            '/users',
            (int) ($count['aggregate'] ?? 0),
            array_map(static fn (array $item): array => [
                'title' => (string) ($item['full_name'] ?? ''),
                'subtitle' => trim((string) (($item['username'] ?? '') !== '' ? '@' . $item['username'] : ($item['email'] ?? ''))),
                'meta' => trim((string) (($item['email'] ?? '') . (($item['phone'] ?? '') !== '' ? ' • ' . $item['phone'] : ''))),
                'badge' => (string) ($item['role_display_name'] ?? ''),
                'href' => '/users/' . (int) $item['id'],
            ], $items)
        );
    }

    private function section(string $key, string $label, string $href, int $count, array $items): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'href' => $href,
            'count' => $count,
            'items' => $items,
        ];
    }

    private function canAccess(array $user, string $permission): bool
    {
        if (($user['role_name'] ?? '') === 'super_admin') {
            return true;
        }

        return in_array($permission, $user['permissions'] ?? [], true);
    }

    private function selectedModules(array|string $modules, array $availableModules): array
    {
        $availableKeys = array_column($availableModules, 'key');
        $requested = is_array($modules) ? $modules : [$modules];
        $requested = array_values(array_filter(array_map(static fn (mixed $value): string => trim((string) $value), $requested)));
        $selected = array_values(array_intersect($availableKeys, $requested));

        return $selected !== [] ? $selected : $availableKeys;
    }

    private function normalizedFilters(array $filters): array
    {
        $normalized = [
            'modules' => $filters['modules'] ?? [],
            'per_section' => max(1, min(10, (int) ($filters['per_section'] ?? 5))),
            'date_from' => $this->normalizeDate($filters['date_from'] ?? null),
            'date_to' => $this->normalizeDate($filters['date_to'] ?? null),
        ];

        foreach (array_keys(self::SECONDARY_FILTERS) as $key) {
            $normalized[$key] = trim((string) ($filters[$key] ?? ''));
        }

        $legacyStatus = mb_strtolower(trim((string) ($filters['status'] ?? '')));
        if ($legacyStatus !== '' && isset(self::STATUS_FILTERS[$legacyStatus])) {
            $legacyFilter = self::STATUS_FILTERS[$legacyStatus];
            $targetKey = (string) ($legacyFilter['key'] ?? '');
            if ($targetKey !== '' && ($normalized[$targetKey] ?? '') === '') {
                $normalized[$targetKey] = (string) ($legacyFilter['value'] ?? '');
            }
        }

        return $normalized;
    }

    private function appliedFilters(array $selectedModules, array $filters): array
    {
        $applied = [
            'modules' => $selectedModules,
            'per_section' => $filters['per_section'],
            'date_from' => $filters['date_from'] ?? '',
            'date_to' => $filters['date_to'] ?? '',
        ];

        foreach (array_keys(self::SECONDARY_FILTERS) as $key) {
            $applied[$key] = $filters[$key] ?? '';
        }

        return $applied;
    }

    private function standardFilterClause(string $statusValue, array $filters, string $statusColumn, string $dateColumn, string $prefix): array
    {
        $clauses = [];
        $bindings = [];

        if ($statusValue !== '') {
            $clauses[] = $statusColumn . ' = :' . $prefix . '_status';
            $bindings[$prefix . '_status'] = $statusValue;
        }

        if (($filters['date_from'] ?? null) !== null) {
            $clauses[] = 'DATE(' . $dateColumn . ') >= :' . $prefix . '_date_from';
            $bindings[$prefix . '_date_from'] = $filters['date_from'];
        }

        if (($filters['date_to'] ?? null) !== null) {
            $clauses[] = 'DATE(' . $dateColumn . ') <= :' . $prefix . '_date_to';
            $bindings[$prefix . '_date_to'] = $filters['date_to'];
        }

        return [
            'sql' => $clauses === [] ? '' : ' AND ' . implode(' AND ', $clauses),
            'bindings' => $bindings,
        ];
    }

    private function inventoryFilterClause(string $status, array $filters): array
    {
        $clauses = [];
        $bindings = [];

        if ($status !== '') {
            if ($status === 'low_stock') {
                $clauses[] = 'ii.quantity_on_hand <= ii.reorder_level';
            } elseif ($status === 'expiring') {
                $clauses[] = 'ii.expiry_date IS NOT NULL AND ii.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)';
            } elseif ($status === 'active' || $status === 'inactive') {
                $clauses[] = 'ii.is_active = :inventory_active';
                $bindings['inventory_active'] = $status === 'active' ? 1 : 0;
            } elseif (str_contains($status, 'low')) {
                $clauses[] = 'ii.quantity_on_hand <= ii.reorder_level';
            } elseif (str_contains($status, 'expir')) {
                $clauses[] = 'ii.expiry_date IS NOT NULL AND ii.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)';
            } else {
                $clauses[] = '(LOWER(ic.name) LIKE :inventory_status OR LOWER(ii.name) LIKE :inventory_status)';
                $bindings['inventory_status'] = '%' . $status . '%';
            }
        }

        if (($filters['date_from'] ?? null) !== null) {
            $clauses[] = 'ii.expiry_date IS NOT NULL AND ii.expiry_date >= :inventory_date_from';
            $bindings['inventory_date_from'] = $filters['date_from'];
        }

        if (($filters['date_to'] ?? null) !== null) {
            $clauses[] = 'ii.expiry_date IS NOT NULL AND ii.expiry_date <= :inventory_date_to';
            $bindings['inventory_date_to'] = $filters['date_to'];
        }

        return [
            'sql' => $clauses === [] ? '' : ' AND ' . implode(' AND ', $clauses),
            'bindings' => $bindings,
        ];
    }

    private function userFilterClause(string $status, array $filters): array
    {
        $clauses = [];
        $bindings = [];

        if ($status !== '') {
            if ($status === 'active' || $status === 'inactive') {
                $clauses[] = 'u.is_active = :users_active';
                $bindings['users_active'] = $status === 'active' ? 1 : 0;
            } else {
                $clauses[] = 'LOWER(r.display_name) LIKE :users_status';
                $bindings['users_status'] = '%' . $status . '%';
            }
        }

        if (($filters['date_from'] ?? null) !== null) {
            $clauses[] = 'DATE(u.created_at) >= :users_date_from';
            $bindings['users_date_from'] = $filters['date_from'];
        }

        if (($filters['date_to'] ?? null) !== null) {
            $clauses[] = 'DATE(u.created_at) <= :users_date_to';
            $bindings['users_date_to'] = $filters['date_to'];
        }

        return [
            'sql' => $clauses === [] ? '' : ' AND ' . implode(' AND ', $clauses),
            'bindings' => $bindings,
        ];
    }

    private function normalizeDate(mixed $value): ?string
    {
        $date = trim((string) $value);

        if ($date === '') {
            return null;
        }

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1 ? $date : null;
    }

    private function likeBindings(string $term, int $count): array
    {
        $bindings = [];
        $value = '%' . $term . '%';

        for ($index = 1; $index <= $count; $index++) {
            $bindings['search_' . $index] = $value;
        }

        return $bindings;
    }

    private static function inventoryBadge(array $item): string
    {
        if (!empty($item['expiry_date']) && strtotime((string) $item['expiry_date']) <= strtotime('+30 days')) {
            return 'Expiring';
        }

        if ((int) ($item['quantity_on_hand'] ?? 0) <= (int) ($item['reorder_level'] ?? 0)) {
            return 'Low Stock';
        }

        return !empty($item['is_active']) ? 'Active' : 'Inactive';
    }
}
