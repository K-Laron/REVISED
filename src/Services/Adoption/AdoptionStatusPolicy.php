<?php

declare(strict_types=1);

namespace App\Services\Adoption;

use RuntimeException;

class AdoptionStatusPolicy
{
    private const LABELS = [
        'pending_review' => 'Pending Review',
        'interview_scheduled' => 'Interview Scheduled',
        'interview_completed' => 'Interview Completed',
        'seminar_scheduled' => 'Seminar Scheduled',
        'seminar_completed' => 'Seminar Completed',
        'pending_payment' => 'Pending Payment',
        'completed' => 'Completed',
        'rejected' => 'Rejected',
        'withdrawn' => 'Withdrawn',
    ];

    private const FLOW = [
        'pending_review' => ['interview_scheduled', 'rejected', 'withdrawn'],
        'interview_scheduled' => ['interview_completed', 'rejected', 'withdrawn'],
        'interview_completed' => ['seminar_scheduled', 'rejected', 'withdrawn'],
        'seminar_scheduled' => ['seminar_completed', 'pending_payment', 'rejected', 'withdrawn'],
        'seminar_completed' => ['pending_payment', 'completed', 'rejected', 'withdrawn'],
        'pending_payment' => ['completed', 'rejected', 'withdrawn'],
        'completed' => [],
        'rejected' => [],
        'withdrawn' => [],
    ];

    public function labels(): array
    {
        return self::LABELS;
    }

    public function availableStatuses(string $currentStatus): array
    {
        return self::FLOW[$currentStatus] ?? [];
    }

    public function buildPipelineStatuses(array $counts): array
    {
        $statuses = [];

        foreach (self::LABELS as $key => $label) {
            $statuses[] = [
                'key' => $key,
                'label' => $label,
                'count' => (int) ($counts[$key] ?? 0),
            ];
        }

        return $statuses;
    }

    public function canTransition(string $currentStatus, string $targetStatus): bool
    {
        if (!isset(self::LABELS[$targetStatus])) {
            return false;
        }

        if ($currentStatus === $targetStatus) {
            return true;
        }

        return in_array($targetStatus, self::FLOW[$currentStatus] ?? [], true);
    }

    public function assertTransition(string $currentStatus, string $targetStatus): void
    {
        if (!isset(self::LABELS[$targetStatus])) {
            throw new RuntimeException('Unknown adoption status.');
        }

        if ($currentStatus === $targetStatus) {
            return;
        }

        if (!in_array($targetStatus, self::FLOW[$currentStatus] ?? [], true)) {
            throw new RuntimeException('The requested status transition is not allowed.');
        }
    }
}
