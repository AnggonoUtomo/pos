<?php

namespace Tests\Feature\Modules\Sales\Returns;

use Tests\TestCase;

class ReturnsScaffoldTest extends TestCase
{
    public function test_module_scaffold_exists(): void
    {
        $this->assertFileExists(app_path('Modules/Sales/Returns/ServiceProvider.php'));
    }
}
