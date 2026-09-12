<?php

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MakeModuleCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->cleanupGeneratedFixtures();
    }

    protected function tearDown(): void
    {
        $this->cleanupGeneratedFixtures();

        parent::tearDown();
    }

    public function test_it_shows_planned_files_without_writing_them_in_dry_run_mode(): void
    {
        $exitCode = Artisan::call('module:make', [
            'category' => 'Testing',
            'module' => 'SampleModule',
            '--with-routes' => true,
            '--with-tests' => true,
            '--dry-run' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('DRY RUN', Artisan::output());
        $this->assertFileDoesNotExist(base_path('Modules/Testing/SampleModule/ServiceProvider.php'));
    }

    public function test_it_generates_a_minimal_module_scaffold_with_routes_and_tests(): void
    {
        $exitCode = Artisan::call('module:make', [
            'category' => 'Testing',
            'module' => 'SampleModule',
            '--with-routes' => true,
            '--with-tests' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertFileExists(base_path('Modules/Testing/SampleModule/ServiceProvider.php'));
        $this->assertFileExists(base_path('Modules/Testing/SampleModule/Presentation/Routes/web.php'));
        $this->assertFileExists(base_path('tests/Feature/Modules/Testing/SampleModule/SampleModuleScaffoldTest.php'));
        $this->assertFileExists(base_path('tests/Unit/Modules/Testing/SampleModule/.gitkeep'));

        $serviceProvider = File::get(base_path('Modules/Testing/SampleModule/ServiceProvider.php'));

        $this->assertStringContainsString('namespace Modules\\Testing\\SampleModule;', $serviceProvider);
        $this->assertStringContainsString("loadRoutesFrom(__DIR__.'/Presentation/Routes/web.php')", $serviceProvider);
        $this->assertStringNotContainsString('Domain', $serviceProvider);
    }

    public function test_it_refuses_to_overwrite_an_existing_module_without_force(): void
    {
        Artisan::call('module:make', [
            'category' => 'Testing',
            'module' => 'SampleModule',
        ]);

        $exitCode = Artisan::call('module:make', [
            'category' => 'Testing',
            'module' => 'SampleModule',
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('sudah ada', Artisan::output());
    }

    private function cleanupGeneratedFixtures(): void
    {
        File::deleteDirectory(base_path('Modules/Testing'));
        File::deleteDirectory(base_path('tests/Feature/Modules/Testing'));
        File::deleteDirectory(base_path('tests/Unit/Modules/Testing'));
    }
}
