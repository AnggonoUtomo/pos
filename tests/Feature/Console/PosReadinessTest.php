<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_check_does_not_pass_with_missing_identity_or_nonproduction_environment(): void
    {
        $this->artisan('pos:readiness --production')->expectsOutput('FAIL: Identitas toko aktual belum diisi')
            ->expectsOutput('FAIL: Environment production dan debug mati')->assertExitCode(1);
    }
}
