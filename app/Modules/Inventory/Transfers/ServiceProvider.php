<?php

namespace App\Modules\Inventory\Transfers;

use App\Modules\Inventory\Transfers\Application\Contracts\TransferDraftRepository;
use App\Modules\Inventory\Transfers\Infrastructure\Persistence\Repositories\EloquentTransferDraftRepository;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TransferDraftRepository::class, EloquentTransferDraftRepository::class);
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
