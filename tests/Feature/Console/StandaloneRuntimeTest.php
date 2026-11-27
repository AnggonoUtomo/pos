<?php

namespace Tests\Feature\Console;

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class StandaloneRuntimeTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = sys_get_temp_dir().'/pos standalone '.bin2hex(random_bytes(8));
        foreach (['config', 'storage', 'cache/release-test'] as $directory) {
            mkdir($this->root.'/'.$directory, 0700, true);
        }
        file_put_contents($this->root.'/config/.env', "APP_ENV=production\n");
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->root);
        parent::tearDown();
    }

    private function probe(array $overrides = [], string $afterBootstrap = ''): Process
    {
        $code = 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; '
            .$afterBootstrap.'echo json_encode([$app->environmentPath(), $app->storagePath(), $app->getCachedConfigPath(), '
            .'$app->getCachedRoutesPath(), $app->getCachedPackagesPath(), $app->getCachedServicesPath(), $app->getCachedEventsPath()]);';
        $process = new Process([PHP_BINARY, '-r', $code], base_path(), array_replace([
            'POS_STANDALONE' => '1', 'POS_DATA_ROOT' => $this->root, 'POS_RELEASE_ID' => 'release-test',
            'APP_CONFIG_CACHE' => base_path('bootstrap/cache/forbidden-production.php'),
        ], $overrides), timeout: 20);
        $process->run();

        return $process;
    }

    public function test_standalone_uses_external_env_storage_and_every_release_cache_path(): void
    {
        $process = $this->probe();
        $this->assertTrue($process->isSuccessful());
        $paths = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
        $normalize = static fn (string $path): string => str_replace('\\', '/', $path);
        $this->assertSame($normalize(realpath($this->root).'/config'), $normalize($paths[0]));
        $this->assertSame($normalize(realpath($this->root).'/storage'), $normalize($paths[1]));
        foreach (array_slice($paths, 2) as $path) {
            $this->assertStringStartsWith($normalize(realpath($this->root).'/cache/release-test/'), $normalize($path));
        }
        $this->assertFileDoesNotExist(base_path('bootstrap/cache/forbidden-production.php'));
    }

    public function test_disabled_mode_does_not_require_standalone_paths_or_change_storage(): void
    {
        $process = $this->probe(['POS_STANDALONE' => '0', 'POS_DATA_ROOT' => '', 'POS_RELEASE_ID' => '']);
        $this->assertTrue($process->isSuccessful());
        $paths = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(base_path(), $paths[0]);
        $this->assertSame(storage_path(), $paths[1]);
    }

    public function test_missing_private_env_is_rejected_without_falling_back_to_store_env(): void
    {
        unlink($this->root.'/config/.env');
        $this->assertFalse($this->probe()->isSuccessful());
    }

    public function test_unsafe_root_release_and_missing_directories_are_rejected(): void
    {
        foreach ([['POS_DATA_ROOT' => base_path()], ['POS_DATA_ROOT' => 'relative'],
            ['POS_RELEASE_ID' => '../escape'], ['POS_RELEASE_ID' => 'missing']] as $overrides) {
            $this->assertFalse($this->probe($overrides)->isSuccessful());
        }
    }

    public function test_default_configuration_leaves_existing_backup_and_tool_resolution_unchanged(): void
    {
        $this->assertFalse(config('standalone.enabled'));
        $this->assertSame(base_path('backups'), config('standalone.backup_path'));
        $this->assertSame('mysql', config('standalone.mysql_binary'));
        $this->assertSame('mysqldump', config('standalone.mysqldump_binary'));
    }

    public function test_mode_selector_cannot_be_changed_after_bootstrap_by_dotenv(): void
    {
        $process = $this->probe(['POS_STANDALONE' => '0'],
            'putenv("POS_STANDALONE=1"); $settings = require "config/standalone.php"; if ($settings["enabled"] !== false) { exit(3); } ');
        $this->assertTrue($process->isSuccessful());
    }
}
