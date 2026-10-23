<?php

namespace App\Modules\Purchasing\Returns;

use App\Modules\Purchasing\Returns\Application\Contracts\ReturnDraftRepository;
use App\Modules\Purchasing\Returns\Application\Contracts\ReturnRefundRepository;
use App\Modules\Purchasing\Returns\Infrastructure\Persistence\Repositories\EloquentReturnDraftRepository;
use App\Modules\Purchasing\Returns\Infrastructure\Persistence\Repositories\EloquentReturnRefundRepository;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ReturnRefundRepository::class, EloquentReturnRefundRepository::class);
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
