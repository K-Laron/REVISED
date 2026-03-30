<?php

declare(strict_types=1);

namespace Tests\Services\Adoption;

use App\Services\Adoption\AdoptionStatusPolicy;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AdoptionStatusPolicyTest extends TestCase
{
    public function testLabelsAndPipelineStatusesStayInSync(): void
    {
        $policy = new AdoptionStatusPolicy();

        self::assertSame(
            [
                'pending_review' => 'Pending Review',
                'interview_scheduled' => 'Interview Scheduled',
                'interview_completed' => 'Interview Completed',
                'seminar_scheduled' => 'Seminar Scheduled',
                'seminar_completed' => 'Seminar Completed',
                'pending_payment' => 'Pending Payment',
                'completed' => 'Completed',
                'rejected' => 'Rejected',
                'withdrawn' => 'Withdrawn',
            ],
            $policy->labels()
        );

        self::assertSame(
            [
                ['key' => 'pending_review', 'label' => 'Pending Review', 'count' => 4],
                ['key' => 'interview_scheduled', 'label' => 'Interview Scheduled', 'count' => 0],
                ['key' => 'interview_completed', 'label' => 'Interview Completed', 'count' => 1],
                ['key' => 'seminar_scheduled', 'label' => 'Seminar Scheduled', 'count' => 0],
                ['key' => 'seminar_completed', 'label' => 'Seminar Completed', 'count' => 0],
                ['key' => 'pending_payment', 'label' => 'Pending Payment', 'count' => 2],
                ['key' => 'completed', 'label' => 'Completed', 'count' => 0],
                ['key' => 'rejected', 'label' => 'Rejected', 'count' => 0],
                ['key' => 'withdrawn', 'label' => 'Withdrawn', 'count' => 0],
            ],
            $policy->buildPipelineStatuses([
                'pending_review' => 4,
                'interview_completed' => 1,
                'pending_payment' => 2,
            ])
        );
    }

    public function testAvailableStatusesAndTransitionChecksMatchWorkflowRules(): void
    {
        $policy = new AdoptionStatusPolicy();

        self::assertSame(
            ['seminar_scheduled', 'rejected', 'withdrawn'],
            $policy->availableStatuses('interview_completed')
        );
        self::assertTrue($policy->canTransition('seminar_completed', 'pending_payment'));
        self::assertTrue($policy->canTransition('pending_payment', 'pending_payment'));
        self::assertFalse($policy->canTransition('pending_review', 'completed'));
        self::assertFalse($policy->canTransition('pending_review', 'missing_status'));
    }

    public function testAssertTransitionRejectsUnknownAndDisallowedStatuses(): void
    {
        $policy = new AdoptionStatusPolicy();

        $policy->assertTransition('pending_review', 'interview_scheduled');
        $policy->assertTransition('completed', 'completed');

        try {
            $policy->assertTransition('pending_review', 'missing_status');
            self::fail('Expected unknown status transition to fail.');
        } catch (RuntimeException $exception) {
            self::assertSame('Unknown adoption status.', $exception->getMessage());
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The requested status transition is not allowed.');
        $policy->assertTransition('pending_review', 'completed');
    }
}
