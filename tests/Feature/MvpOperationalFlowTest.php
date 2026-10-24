<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fixtures\MvpOperationalScenario;
use Tests\TestCase;

class MvpOperationalFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_paid_store_workflow_reconciles_stock_cash_returns_and_reports(): void
    {
        $result = MvpOperationalScenario::run();
        $this->assertSame('PASS', $result['status']);
        $this->assertSame('199800.00', $result['invoice_total']);
        $this->assertSame('900000.000000', $result['stock_value']);
    }
}
