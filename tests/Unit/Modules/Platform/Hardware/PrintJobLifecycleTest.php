<?php

namespace Tests\Unit\Modules\Platform\Hardware;

use App\Modules\Platform\Hardware\Domain\Services\PrintJobLifecycle;
use LogicException;
use PHPUnit\Framework\TestCase;

class PrintJobLifecycleTest extends TestCase
{
    public function test_only_queued_jobs_can_be_claimed_and_outcomes_are_conservative(): void
    {
        PrintJobLifecycle::assertClaimable('queued');
        foreach (['sending', 'submitted', 'uncertain', 'failed_before_send'] as $status) {
            try {
                PrintJobLifecycle::assertClaimable($status);
                $this->fail('Job claimed twice');
            } catch (LogicException) {
                $this->addToAssertionCount(1);
            }
        }
        $this->assertTrue(PrintJobLifecycle::canReprint('submitted'));
        $this->assertTrue(PrintJobLifecycle::canReprint('uncertain'));
        $this->assertFalse(PrintJobLifecycle::canReprint('queued'));
        $this->assertFalse(PrintJobLifecycle::canReprint('sending'));
    }
}
