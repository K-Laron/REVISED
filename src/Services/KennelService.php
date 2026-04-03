<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Request;
use App\Models\Animal;
use App\Models\Kennel;
use App\Models\KennelAssignment;
use App\Models\KennelMaintenanceLog;
use App\Support\MediaPath;
use RuntimeException;

class KennelService
{
    private Kennel $kennels;
    private KennelAssignment $assignments;
    private KennelMaintenanceLog $maintenance;
    private Animal $animals;
    private AuditService $audit;

    public function __construct(
        ?Kennel $kennels = null,
        ?KennelAssignment $assignments = null,
        ?KennelMaintenanceLog $maintenance = null,
        ?Animal $animals = null,
        ?AuditService $audit = null
    )
    {
        $this->kennels = $kennels ?? new Kennel();
        $this->assignments = $assignments ?? new KennelAssignment();
        $this->maintenance = $maintenance ?? new KennelMaintenanceLog();
        $this->animals = $animals ?? new Animal();
        $this->audit = $audit ?? new AuditService();
    }

    public function list(array $filters = []): array
    {
        $kennels = $this->kennels->list($filters);
        $occupants = $this->assignments->currentByKennelIds(array_column($kennels, 'id'));

        $groupedOccupants = [];
        foreach ($occupants as $occupant) {
            $groupedOccupants[(int) $occupant['kennel_id']][] = $this->formatOccupant($occupant);
        }

        foreach ($kennels as &$kennel) {
            $kennel['current_occupants'] = $groupedOccupants[(int) $kennel['id']] ?? [];
            $kennel['occupancy_count'] = count($kennel['current_occupants']);
            $kennel['can_assign'] = $kennel['status'] === 'Available' && $kennel['occupancy_count'] < (int) $kennel['max_occupants'];
        }
        unset($kennel);

        return $kennels;
    }

    public function stats(): array
    {
        $rows = Database::fetchAll(
            "SELECT status, COUNT(*) AS aggregate
             FROM kennels
             WHERE is_deleted = 0
             GROUP BY status"
        );

        $statusCounts = [
            'Available' => 0,
            'Occupied' => 0,
            'Maintenance' => 0,
            'Quarantine' => 0,
        ];

        foreach ($rows as $row) {
            $statusCounts[$row['status']] = (int) $row['aggregate'];
        }

        $total = array_sum($statusCounts);

        return [
            'total' => $total,
            'available' => $statusCounts['Available'],
            'occupied' => $statusCounts['Occupied'],
            'maintenance' => $statusCounts['Maintenance'],
            'quarantine' => $statusCounts['Quarantine'],
            'occupancy_rate' => $total > 0 ? round(($statusCounts['Occupied'] / $total) * 100, 1) : 0.0,
        ];
    }

    public function get(int $kennelId): array
    {
        $kennel = $this->kennels->find($kennelId);
        if ($kennel === false) {
            throw new RuntimeException('Kennel not found.');
        }

        $kennel['current_occupants'] = array_map($this->formatOccupant(...), $this->assignments->currentByKennel($kennelId));
        $kennel['occupancy_count'] = count($kennel['current_occupants']);
        $kennel['history'] = $this->assignments->history($kennelId);
        $kennel['maintenance_logs'] = $this->maintenance->listByKennel($kennelId);

        return $kennel;
    }

    public function assignableAnimals(): array
    {
        return Database::fetchAll(
            "SELECT a.id, a.animal_id, a.name, a.species, a.size, a.status, k.kennel_code AS current_kennel_code
             FROM animals a
             LEFT JOIN kennel_assignments ka ON ka.animal_id = a.id AND ka.released_at IS NULL
             LEFT JOIN kennels k ON k.id = ka.kennel_id
             WHERE a.is_deleted = 0
               AND a.status NOT IN ('Adopted', 'Deceased', 'Transferred')
             ORDER BY a.created_at DESC"
        );
    }

    public function existingKennelCodes(): array
    {
        $rows = Database::fetchAll('SELECT kennel_code FROM kennels ORDER BY kennel_code ASC');

        return array_values(array_filter(array_map(
            static fn (array $row): string => (string) ($row['kennel_code'] ?? ''),
            $rows
        )));
    }

    public function zones(): array
    {
        $rows = Database::fetchAll(
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

    public function generateKennelCode(array $data, ?int $ignoreId = null): string
    {
        $zoneToken = $this->extractZoneToken((string) ($data['zone'] ?? ''));
        if ($zoneToken === '') {
            return '';
        }

        $sql = 'SELECT kennel_code FROM kennels WHERE kennel_code LIKE :prefix';
        $bindings = ['prefix' => 'K-' . $zoneToken . '%'];

        if ($ignoreId !== null) {
            $sql .= ' AND id <> :id';
            $bindings['id'] = $ignoreId;
        }

        $rows = Database::fetchAll($sql, $bindings);
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

    public function create(array $data, int $userId, Request $request): array
    {
        $payload = $this->normalizePayload($data, $userId, true);
        if ($payload['kennel_code'] === '') {
            $payload['kennel_code'] = $this->generateKennelCode($data);
        }

        if ($this->kennels->codeExists((string) $payload['kennel_code'])) {
            throw new RuntimeException('Kennel code is already in use.');
        }

        $kennelId = $this->kennels->create($payload);
        $kennel = $this->get($kennelId);
        $this->audit->record($userId, 'create', 'kennels', 'kennels', $kennelId, [], $kennel, $request);

        return $kennel;
    }

    public function update(int $kennelId, array $data, int $userId, Request $request): array
    {
        $current = $this->get($kennelId);
        $payload = $this->normalizePayload($data, $userId, false);

        if ($this->kennels->codeExists((string) $payload['kennel_code'], $kennelId)) {
            throw new RuntimeException('Kennel code is already in use.');
        }

        $this->kennels->update($kennelId, $payload);
        $kennel = $this->get($kennelId);
        $this->audit->record($userId, 'update', 'kennels', 'kennels', $kennelId, $current, $kennel, $request);

        return $kennel;
    }

    public function delete(int $kennelId, int $userId, Request $request): void
    {
        $kennel = $this->get($kennelId);
        if ($kennel['current_occupants'] !== []) {
            throw new RuntimeException('Occupied kennels cannot be deleted.');
        }

        $this->kennels->setDeleted($kennelId, true);
        $this->audit->record($userId, 'delete', 'kennels', 'kennels', $kennelId, $kennel, ['is_deleted' => true], $request);
    }

    public function assignAnimal(int $kennelId, int $animalId, ?string $reason, int $userId, Request $request): array
    {
        $kennel = $this->get($kennelId);
        $animal = $this->animals->find($animalId);

        if ($animal === false) {
            throw new RuntimeException('Animal not found.');
        }

        if ($kennel['status'] !== 'Available') {
            throw new RuntimeException('Only available kennels can receive animals.');
        }

        if ($kennel['occupancy_count'] >= (int) $kennel['max_occupants']) {
            throw new RuntimeException('Kennel is already at capacity.');
        }

        if (!in_array($kennel['allowed_species'], ['Any', $animal['species']], true)) {
            throw new RuntimeException('Animal species is not allowed in this kennel.');
        }

        if ($kennel['size_category'] !== $animal['size']) {
            throw new RuntimeException('Animal size does not match this kennel.');
        }

        Database::beginTransaction();

        try {
            $previousAssignment = $this->assignments->currentByAnimal($animalId);
            if ($previousAssignment !== false) {
                $this->assignments->releaseByAnimal($animalId, $userId, $reason ?: 'Transferred to another kennel');
                $this->kennels->setStatus((int) $previousAssignment['kennel_id'], 'Available', $userId);
            }

            $this->assignments->create($kennelId, $animalId, $userId);
            $this->kennels->setStatus($kennelId, 'Occupied', $userId);
            Database::execute('UPDATE animals SET updated_by = :updated_by WHERE id = :id', ['updated_by' => $userId, 'id' => $animalId]);
            Database::commit();
        } catch (\Throwable $exception) {
            Database::rollBack();
            throw $exception;
        }

        $updated = $this->get($kennelId);
        $this->audit->record(
            $userId,
            'update',
            'kennels',
            'kennel_assignments',
            $kennelId,
            ['kennel' => $kennel['kennel_code'], 'occupants' => $kennel['current_occupants']],
            ['animal_id' => $animal['animal_id'], 'reason' => $reason],
            $request
        );

        return $updated;
    }

    public function releaseAnimal(int $kennelId, ?string $reason, int $userId, Request $request): array
    {
        $kennel = $this->get($kennelId);
        if ($kennel['current_occupants'] === []) {
            throw new RuntimeException('Kennel does not have an active occupant.');
        }

        Database::beginTransaction();

        try {
            $this->assignments->releaseByKennel($kennelId, $userId, $reason ?: 'Released from kennel');
            $this->kennels->setStatus($kennelId, 'Available', $userId);
            Database::commit();
        } catch (\Throwable $exception) {
            Database::rollBack();
            throw $exception;
        }

        $updated = $this->get($kennelId);
        $this->audit->record(
            $userId,
            'update',
            'kennels',
            'kennel_assignments',
            $kennelId,
            ['occupants' => $kennel['current_occupants']],
            ['released' => true, 'reason' => $reason],
            $request
        );

        return $updated;
    }

    public function history(int $kennelId): array
    {
        $this->get($kennelId);

        return $this->assignments->history($kennelId);
    }

    public function logMaintenance(int $kennelId, array $data, int $userId, Request $request): array
    {
        $kennel = $this->get($kennelId);
        if ($kennel['current_occupants'] !== []) {
            throw new RuntimeException('Release the current animal before putting the kennel under maintenance.');
        }

        $logId = $this->maintenance->create([
            'kennel_id' => $kennelId,
            'maintenance_type' => $data['maintenance_type'],
            'description' => $data['description'] !== '' ? $data['description'] : null,
            'scheduled_date' => $data['scheduled_date'] !== '' ? $data['scheduled_date'] : null,
            'completed_at' => ($data['completed_at'] ?? '') !== '' ? str_replace('T', ' ', (string) $data['completed_at']) . ':00' : null,
            'performed_by' => $userId,
        ]);

        $status = ($data['completed_at'] ?? '') !== '' ? 'Available' : 'Maintenance';
        $this->kennels->setStatus($kennelId, $status, $userId);

        $updated = $this->get($kennelId);
        $this->audit->record(
            $userId,
            'create',
            'kennels',
            'kennel_maintenance_logs',
            $logId,
            [],
            ['kennel_id' => $kennelId, 'status' => $status, 'maintenance_type' => $data['maintenance_type']],
            $request
        );

        return $updated;
    }

    public function maintenanceHistory(int $kennelId): array
    {
        $this->get($kennelId);

        return $this->maintenance->listByKennel($kennelId);
    }

    private function normalizePayload(array $data, int $userId, bool $creating): array
    {
        $status = $data['status'] ?? 'Available';

        return [
            'kennel_code' => trim((string) $data['kennel_code']),
            'zone' => trim((string) $data['zone']),
            'row_number' => trim((string) ($data['row_number'] ?? '')) !== '' ? trim((string) $data['row_number']) : null,
            'size_category' => $data['size_category'],
            'type' => $data['type'],
            'allowed_species' => $data['allowed_species'],
            'max_occupants' => max(1, (int) $data['max_occupants']),
            'status' => $status,
            'notes' => trim((string) ($data['notes'] ?? '')) !== '' ? trim((string) $data['notes']) : null,
            'created_by' => $creating ? $userId : null,
            'updated_by' => $userId,
        ];
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

    private function formatOccupant(array $occupant): array
    {
        $assignedAt = strtotime((string) $occupant['assigned_at']) ?: time();

        return [
            'assignment_id' => (int) $occupant['id'],
            'animal_id' => (int) $occupant['animal_id'],
            'animal_code' => $occupant['animal_code'],
            'animal_name' => $occupant['animal_name'],
            'species' => $occupant['species'],
            'size' => $occupant['size'],
            'animal_status' => $occupant['animal_status'],
            'assigned_at' => $occupant['assigned_at'],
            'days_housed' => max(0, (int) floor((time() - $assignedAt) / 86400)),
            'primary_photo_path' => MediaPath::normalizePublicImagePath($occupant['primary_photo_path'] ?? null),
        ];
    }
}
