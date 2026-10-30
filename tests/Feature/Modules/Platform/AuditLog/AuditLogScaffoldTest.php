<?php

namespace Tests\Feature\Modules\Platform\AuditLog;

use Tests\TestCase;

class AuditLogScaffoldTest extends TestCase
{
    public function test_module_scaffold_exists(): void
    {
        $this->assertFileExists(app_path('Modules/Platform/AuditLog/ServiceProvider.php'));
    }
}
