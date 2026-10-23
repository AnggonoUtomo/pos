<?php

namespace Tests\Feature\Modules\Inventory\Transfers;

use Tests\TestCase;

class TransfersScaffoldTest extends TestCase
{
    public function test_module_scaffold_exists(): void
    {
        $this->assertFileExists(app_path('Modules/Inventory/Transfers/ServiceProvider.php'));
    }
}
