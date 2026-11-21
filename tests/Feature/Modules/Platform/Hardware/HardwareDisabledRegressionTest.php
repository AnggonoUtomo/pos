<?php

namespace Tests\Feature\Modules\Platform\Hardware;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HardwareDisabledRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_off_cannot_be_bypassed_even_by_super_system(): void
    {
        config(['pos_hardware.enabled' => false]);
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
        $this->withoutVite()->get('/platform/hardware/scanner')->assertOk();
        $this->getJson('/platform/hardware/scanner/resolve?code=UNKNOWN')->assertUnprocessable();
        config(['pos_hardware.enabled' => false]);
        foreach (['/platform/hardware/scanner', '/platform/hardware/scanner/resolve?code=DEMO'] as $url) {
            $this->getJson($url)->assertNotFound();
        }
        $this->app->instance('env', 'testing');
        config(['pos_hardware.enabled' => false]);
        $this->getJson('/platform/hardware/scanner')->assertNotFound();
    }

    public function test_production_on_still_requires_session_and_permission(): void
    {
        config(['pos_hardware.enabled' => true]);
        $this->app->instance('env', 'production');
        $this->get('/platform/hardware/scanner')->assertRedirect('/login');
        $this->getJson('/platform/hardware/scanner/resolve?code=UNKNOWN')->assertUnauthorized();

        $user = User::factory()->create();
        $this->actingAs($user)->get('/platform/hardware/scanner')->assertForbidden();
        $this->getJson('/platform/hardware/scanner/resolve?code=UNKNOWN')->assertForbidden();

        $user->givePermissionTo(Permission::findOrCreate('inventory.view', 'web'));
        $response = $this->withoutVite()->get('/platform/hardware/scanner')->assertOk();
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->getJson('/platform/hardware/scanner/resolve?code=UNKNOWN')->assertUnprocessable()
            ->assertJsonValidationErrors('code');
    }

    public function test_off_does_not_install_navigation_or_global_keyboard_capture(): void
    {
        config(['pos_hardware.enabled' => false, 'pos_hardware.printing_enabled' => true]);
        $this->actingAs(User::factory()->create());
        $this->withoutVite()->get('/settings/profile')->assertOk()->assertViewHas('page',
            fn (array $page) => $page['props']['hardware'] === ['enabled' => false, 'printing_enabled' => false]);
        $this->getJson('/platform/hardware')->assertNotFound();
        $this->postJson('/platform/hardware/labels', [])->assertNotFound();
        foreach (['resources/js/pages/sales/pos/index.tsx', 'resources/js/pages/sales/pos/hooks/use-pos-scanner.ts'] as $path) {
            $this->assertStringNotContainsString('platform.hardware', file_get_contents(base_path($path)));
            $this->assertStringNotContainsString('/platform/hardware', file_get_contents(base_path($path)));
        }
        foreach (['resources/js/components/app-sidebar.tsx', 'resources/js/components/command-palette.tsx'] as $path) {
            $this->assertStringContainsString('hardware?.enabled', file_get_contents(base_path($path)));
            $this->assertStringNotContainsString('qz-tray', file_get_contents(base_path($path)));
        }
    }
}
