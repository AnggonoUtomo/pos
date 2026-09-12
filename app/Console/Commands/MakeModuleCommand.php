<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeModuleCommand extends Command
{
    protected $signature = 'module:make
        {category : Module category namespace, for example Platform or Inventory}
        {module : Module name namespace, for example Identity or Catalog}
        {--with-routes : Generate Presentation/Routes/web.php}
        {--with-tests : Generate module test placeholders}
        {--force : Overwrite existing generated files}
        {--dry-run : Show planned files without writing them}';

    protected $description = 'Create a minimal DDD-lite module scaffold.';

    public function handle(): int
    {
        $category = $this->normalizeSegment((string) $this->argument('category'));
        $module = $this->normalizeSegment((string) $this->argument('module'));

        if ($category === null || $module === null) {
            $this->error('Category dan module hanya boleh berisi huruf, angka, spasi, underscore, atau dash.');

            return self::FAILURE;
        }

        $modulePath = base_path("Modules/{$category}/{$module}");
        $files = $this->plannedFiles($category, $module);

        if (File::exists($modulePath) && ! $this->option('force') && ! $this->option('dry-run')) {
            $this->error("Module {$category}/{$module} sudah ada. Gunakan --force jika ingin overwrite file generated.");

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->info("DRY RUN module {$category}/{$module}");

            foreach (array_keys($files) as $path) {
                $this->line($this->relativePath($path));
            }

            return self::SUCCESS;
        }

        foreach ($files as $path => $content) {
            File::ensureDirectoryExists(dirname($path));

            if (File::exists($path) && ! $this->option('force')) {
                $this->warn('Skip existing: '.$this->relativePath($path));

                continue;
            }

            File::put($path, $content);
            $this->line('Created: '.$this->relativePath($path));
        }

        $this->info("Module {$category}/{$module} berhasil dibuat.");

        return self::SUCCESS;
    }

    /**
     * @return array<string, string>
     */
    private function plannedFiles(string $category, string $module): array
    {
        $files = [
            base_path("Modules/{$category}/{$module}/ServiceProvider.php") => $this->serviceProviderStub($category, $module),
        ];

        if ($this->option('with-routes')) {
            $files[base_path("Modules/{$category}/{$module}/Presentation/Routes/web.php")] = $this->webRouteStub();
        }

        if ($this->option('with-tests')) {
            $files[base_path("tests/Feature/Modules/{$category}/{$module}/{$module}ScaffoldTest.php")] = $this->featureTestStub($category, $module);
            $files[base_path("tests/Unit/Modules/{$category}/{$module}/.gitkeep")] = '';
        }

        return $files;
    }

    private function normalizeSegment(string $value): ?string
    {
        $value = trim($value);

        if ($value === '' || ! preg_match('/^[A-Za-z][A-Za-z0-9 _-]*$/', $value)) {
            return null;
        }

        return Str::studly($value);
    }

    private function serviceProviderStub(string $category, string $module): string
    {
        return <<<PHP
<?php

namespace Modules\\{$category}\\{$module};

use Illuminate\\Support\\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (is_file(__DIR__.'/Presentation/Routes/web.php')) {
            \$this->loadRoutesFrom(__DIR__.'/Presentation/Routes/web.php');
        }

        if (is_dir(__DIR__.'/Database/Migrations')) {
            \$this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        }
    }
}

PHP;
    }

    private function webRouteStub(): string
    {
        return <<<'PHP'
<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->group(function () {
    //
});

PHP;
    }

    private function featureTestStub(string $category, string $module): string
    {
        return <<<PHP
<?php

namespace Tests\\Feature\\Modules\\{$category}\\{$module};

use Tests\\TestCase;

class {$module}ScaffoldTest extends TestCase
{
    public function test_module_scaffold_exists(): void
    {
        \$this->assertFileExists(base_path('Modules/{$category}/{$module}/ServiceProvider.php'));
    }
}

PHP;
    }

    private function relativePath(string $path): string
    {
        return str_replace(base_path().DIRECTORY_SEPARATOR, '', $path);
    }
}
