<?php

declare(strict_types=1);

namespace App\Services\Reports;

final class ReportRange
{
    public function __construct(
        public readonly string $startDate,
        public readonly string $endDate,
        public readonly string $groupBy
    ) {
    }

    public static function fromFilters(array $filters): self
    {
        $groupBy = $filters['group_by'] ?? 'month';
        if (!in_array($groupBy, ['day', 'week', 'month', 'quarter', 'year'], true)) {
            $groupBy = 'month';
        }

        return new self(
            (string) ($filters['start_date'] ?? date('Y-m-01')),
            (string) ($filters['end_date'] ?? date('Y-m-d')),
            $groupBy
        );
    }

    public function toArray(): array
    {
        return [
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'group_by' => $this->groupBy,
        ];
    }
}
