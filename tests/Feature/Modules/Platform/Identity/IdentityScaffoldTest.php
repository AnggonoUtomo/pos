<?php

namespace Tests\Feature\Modules\Platform\Identity;

use Tests\TestCase;

class IdentityScaffoldTest extends TestCase
{
    public function test_module_scaffold_exists(): void
    {
        $this->assertFileExists(app_path('Modules/Platform/Identity/ServiceProvider.php'));
    }
}
