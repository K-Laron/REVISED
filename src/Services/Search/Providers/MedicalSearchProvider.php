<?php

declare(strict_types=1);

namespace App\Services\Search\Providers;

use App\Core\Database;
use App\Services\Search\AbstractSearchProvider;

final class MedicalSearchProvider extends AbstractSearchProvider
{
    public function secondaryFilters(): array
    {
        return [
            'medical_type' => [
                'label' => 'Procedure Type',
                'options' => [
                    ['value' => 'vaccination', 'label' => 'Vaccination'],
                    ['value' => 'treatment', 'label' => 'Treatment'],
                    ['value' => 'surgery', 'label' => 'Surgery'],
                    ['value' => 'examination', 'label' => 'Examination'],
                ],
            ],
        ];
    }

    public function legacyStatusAliases(): array
    {
        return [
            'medical_vaccination' => ['key' => 'medical_type', 'value' => 'vaccination'],
            'medical_treatment' => ['key' => 'medical_type', 'value' => 'treatment'],
            'medical_surgery' => ['key' => 'medical_type', 'value' => 'surgery'],
            'medical_examination' => ['key' => 'medical_type', 'value' => 'examination'],
        ];
    }

    public function key(): string
    {
        return 'medical';
    }

    public function label(): string
    {
        return 'Medical Records';
    }

    public function permission(): string
    {
        return 'medical.read';
    }

    public function search(string $term, int $limit, array $filters): array
    {
        $bindings = $this->likeBindings($term, 3);
        $filterClause = $this->standardFilterClause((string) ($filters['medical_type'] ?? ''), $filters, 'mr.procedure_type', 'mr.record_date', 'medical');
        $rows = $this->db->fetchAll(
            "SELECT mr.id, mr.procedure_type, mr.record_date, a.id AS animal_id, a.animal_id AS animal_code, a.name AS animal_name
             FROM medical_records mr
             INNER JOIN animals a ON a.id = mr.animal_id
             WHERE mr.is_deleted = 0
               AND (a.animal_id LIKE :search_1 OR a.name LIKE :search_2 OR mr.general_notes LIKE :search_3)"
               . $filterClause['sql'] . "
             ORDER BY mr.record_date DESC, mr.id DESC
             LIMIT " . ($limit + 1),
            $bindings + $filterClause['bindings']
        );
        $preview = $this->previewResult(
            $rows,
            $limit,
            fn (): int => (int) (($this->db->fetch(
                "SELECT COUNT(*) AS aggregate
                 FROM medical_records mr
                 INNER JOIN animals a ON a.id = mr.animal_id
                 WHERE mr.is_deleted = 0
                   AND (a.animal_id LIKE :search_1 OR a.name LIKE :search_2 OR mr.general_notes LIKE :search_3)"
                   . $filterClause['sql'],
                $bindings + $filterClause['bindings']
            )['aggregate'] ?? 0))
        );

        return $this->section(
            'medical',
            'Medical Records',
            '/medical',
            $preview['count'],
            array_map(static fn (array $item): array => [
                'title' => (string) ($item['animal_name'] ?: $item['animal_code']),
                'subtitle' => trim((string) (($item['animal_code'] ?? '') . ' • ' . ucfirst((string) $item['procedure_type']))),
                'meta' => (string) ($item['record_date'] ?? ''),
                'badge' => ucfirst((string) ($item['procedure_type'] ?? '')),
                'href' => '/medical/' . (int) $item['id'],
            ], $preview['items'])
        );
    }
}
