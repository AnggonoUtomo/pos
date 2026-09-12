<?php

declare(strict_types=1);

namespace App\Modules\HumanResource\HumanResource;

use App\Modules\HumanResource\HumanResource\Application\Contracts\ActiveEmployeeReader;
use App\Modules\HumanResource\HumanResource\Application\Contracts\HumanResourceActivityPublisher;
use App\Modules\HumanResource\HumanResource\Application\Contracts\HumanResourceRepository;
use App\Modules\HumanResource\HumanResource\Infrastructure\Events\LaravelHumanResourceActivityPublisher;
use App\Modules\HumanResource\HumanResource\Infrastructure\Readers\EloquentActiveEmployeeReader;
use App\Modules\HumanResource\HumanResource\Infrastructure\Repositories\EloquentHumanResourceRepository;
use Illuminate\Support\ServiceProvider as FrameworkServiceProvider;

final class ServiceProvider extends FrameworkServiceProvider
{
    public function register(): void
    {
        $this->app->bind(HumanResourceActivityPublisher::class, LaravelHumanResourceActivityPublisher::class);
        $this->app->bind(HumanResourceRepository::class, EloquentHumanResourceRepository::class);
        $this->app->bind(ActiveEmployeeReader::class, EloquentActiveEmployeeReader::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        $this->loadRoutesFrom(__DIR__.'/Routes/web.php');
        $this->loadRoutesFrom(__DIR__.'/Routes/api.php');
    }
}
