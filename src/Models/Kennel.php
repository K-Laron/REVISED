<?php

declare(strict_types=1);

namespace App\Models;

class Kennel extends BaseModel
{
    protected static string $table = 'kennels';

    public function list(array $filters = []): array
    {
        [$whereSql, $bindings] = $this->buildFilters($filters);

        return $this->db->fetchAll(
            "SELECT *
             FROM `kennels`
             {$whereSql}
             ORDER BY `zone` ASC, `kennel_code` ASC",
            $bindings
        );
    }

    public function setStatus(int $id, string $status, ?int $updatedBy): void
    {
        $this->update($id, [
            'status' => $status,
            'updated_by' => $updatedBy,
        ]);
    }

    public function codeExists(string $code, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT `id` FROM `kennels` WHERE `kennel_code` = :kennel_code';
        $bindings = ['kennel_code' => $code];

        if ($ignoreId !== null) {
            $sql .= ' AND `id` <> :id';
            $bindings['id'] = $ignoreId;
        }

        $sql .= ' LIMIT 1';

        return $this->db->fetch($sql, $bindings) !== false;
    }

    private function buildFilters(array $filters): array
    {
        $clauses = ['`is_deleted` = 0'];
        $bindings = [];

        foreach (['zone', 'status', 'allowed_species', 'size_category', 'type'] as $field) {
            if (($filters[$field] ?? '') !== '') {
                $clauses[] = "`{$field}` = :{$field}";
                $bindings[$field] = $filters[$field];
            }
        }

        return ['WHERE ' . implode(' AND ', $clauses), $bindings];
    }

    public function getStats(): array
    {
        $rows = $this->db->fetchAll(
            "SELECT status, COUNT(*) AS aggregate
             FROM kennels
             WHERE is_deleted = 0
             GROUP BY status"
        );

        $counts = [
            'Available' => 0,
            'Occupied' => 0,
            'Maintenance' => 0,
            'Quarantine' => 0,
        ];

        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['aggregate'];
        }

        return $counts;
    }

    public function existingCodes(): array
    {
        $rows = $this->db->fetchAll('SELECT kennel_code FROM kennels ORDER BY kennel_code ASC');

        return array_values(array_filter(array_map(
            static fn (array $row): string => (string) ($row['kennel_code'] ?? ''),
            $rows
        )));
    }

    public function listZones(): array
    {
        $rows = $this->db->fetchAll(
            'SELECT DISTINCT `zone`
             FROM `kennels`
             WHERE `is_deleted` = 0
             ORDER BY `zone` ASC'
        );

        return array_values(array_filter(array_map(
            static fn (array $row): string => trim((string) ($row['zone'] ?? '')),
            $rows
        )));
    }

    public function nextCodeForZone(string $zone, ?int $ignoreId = null): string
    {
        $zoneToken = $this->extractZoneToken($zone);
        if ($zoneToken === '') {
            return '';
        }

        $sql = 'SELECT kennel_code FROM kennels WHERE kennel_code LIKE :prefix';
        $bindings = ['prefix' => 'K-' . $zoneToken . '%'];

        if ($ignoreId !== null) {
            $sql .= ' AND id <> :id';
            $bindings['id'] = $ignoreId;
        }

        $rows = $this->db->fetchAll($sql, $bindings);
        $nextSequence = 1;

        foreach ($rows as $row) {
            $code = (string) ($row['kennel_code'] ?? '');
            if (!preg_match('/^K-' . preg_quote($zoneToken, '/') . '(\d+)$/', $code, $matches)) {
                continue;
            }

            $nextSequence = max($nextSequence, ((int) $matches[1]) + 1);
        }

        return sprintf('K-%s%02d', $zoneToken, $nextSequence);
    }

    private function extractZoneToken(string $zone): string
    {
        $zone = strtoupper(trim($zone));
        if ($zone === '') {
            return '';
        }

        preg_match_all('/[A-Z0-9]+/', $zone, $matches);
        $parts = $matches[0] ?? [];
        if ($parts === []) {
            return '';
        }

        $token = (string) end($parts);
        if (strlen($token) <= 3) {
            return $token;
        }

        return substr($token, 0, 3);
    }

    public function listAvailableForSelection(?int $includeKennelId = null): array
    {
        $sql = "SELECT id, kennel_code, zone, size_category, allowed_species, status
                FROM kennels
                WHERE is_deleted = 0 AND (status = 'Available'";
        $bindings = [];

        if ($includeKennelId !== null) {
            $sql .= ' OR id = :id';
            $bindings['id'] = $includeKennelId;
        }

        $sql .= ') ORDER BY zone, kennel_code';

        return $this->db->fetchAll($sql, $bindings);
    }

    public function isAvailable(int $id): bool
    {
        $kennel = $this->db->fetch(
            'SELECT status FROM kennels WHERE id = :id AND is_deleted = 0 LIMIT 1',
            ['id' => $id]
        );

        return ($kennel !== false && ($kennel['status'] ?? '') === 'Available');
    }

    public function getOccupancyCount(): int
    {
        $row = $this->db->fetch(
            'SELECT COUNT(*) AS occupied_kennels
             FROM kennel_assignments
             WHERE released_at IS NULL'
        );

        return (int) ($row['occupied_kennels'] ?? 0);
    }
}
