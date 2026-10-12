<?php

namespace Tests\Feature\Modules\Inventory\Stock;

use Tests\TestCase;

class StockScaffoldTest extends TestCase
{
    public function test_module_scaffold_exists(): void
    {
        $this->assertFileExists(app_path('Modules/Inventory/Stock/ServiceProvider.php'));
    }
}
