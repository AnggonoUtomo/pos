<?php

namespace Tests\Feature\Modules\Platform\Hardware;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HardwareDisabledRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_is_off_and_even_super_system_cannot_bypass_it(): void
    {
        $this->assertFalse(config('pos_hardware.enabled'));
        $user = User::factory()->create()->assignRole(Role::findOrCreate('super-system', 'web'));
        $this->actingAs($user);
        foreach (['/platform/hardware/scanner', '/platform/hardware/scanner/resolve?code=DEMO'] as $url) {
            $this->getJson($url)->assertNotFound();
        }
    }

    public function test_registered_routes_are_runtime_guarded_after_environment_or_flag_changes(): void
    {
        $this->actingAs(User::factory()->create()->assignRole(Role::findOrCreate('super-system', 'web')));
        config(['pos_hardware.enabled' => true]);
        $this->withoutVite()->get('/platform/hardware/scanner')->assertOk();
        $this->app['router']->setCompiledRoutes($this->app['router']->getRoutes()->compile());
        $this->app->instance('env', 'production');
        foreach (['/platform/hardware/scanner', '/platform/hardware/scanner/resolve?code=DEMO'] as $url) {
            $this->getJson($url)->assertNotFound();
        }
        $this->app->instance('env', 'testing');
        config(['pos_hardware.enabled' => false]);
        $this->getJson('/platform/hardware/scanner')->assertNotFound();
    }

    public function test_off_does_not_install_navigation_or_global_keyboard_capture(): void
    {
        foreach (['resources/js/components/app-sidebar.tsx', 'resources/js/components/command-palette.tsx',
            'resources/js/pages/sales/pos/index.tsx', 'resources/js/pages/sales/pos/hooks/use-pos-scanner.ts'] as $path) {
            $this->assertStringNotContainsString('platform.hardware', file_get_contents(base_path($path)));
            $this->assertStringNotContainsString('/platform/hardware', file_get_contents(base_path($path)));
        }
    }
}
