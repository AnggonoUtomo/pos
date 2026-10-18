<?php

namespace App\Modules\Sales\Returns;

use App\Modules\Sales\Returns\Application\Contracts\ReturnDraftRepository;
use App\Modules\Sales\Returns\Infrastructure\Persistence\Repositories\EloquentReturnDraftRepository;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ReturnDraftRepository::class, EloquentReturnDraftRepository::class);
    }

    public function boot(): void
    {
        if (is_file(__DIR__.'/Routes/web.php')) {
            $this->loadRoutesFrom(__DIR__.'/Routes/web.php');
        }

        if (is_dir(__DIR__.'/Database/Migrations')) {
            $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        }
    }
}
