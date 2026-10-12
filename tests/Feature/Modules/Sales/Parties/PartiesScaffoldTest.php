<?php

namespace Tests\Feature\Modules\Sales\Parties;

use Tests\TestCase;

class PartiesScaffoldTest extends TestCase
{
    public function test_module_scaffold_exists(): void
    {
        $this->assertFileExists(app_path('Modules/Sales/Parties/ServiceProvider.php'));
    }
}
