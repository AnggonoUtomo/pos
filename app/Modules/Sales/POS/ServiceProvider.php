<?php

namespace App\Modules\Sales\POS;

use App\Modules\Sales\POS\Application\Contracts\InvoiceHistoryReader;
use App\Modules\Sales\POS\Application\Contracts\InvoiceRepository;
use App\Modules\Sales\POS\Infrastructure\Persistence\Repositories\DatabaseInvoiceHistoryReader;
use App\Modules\Sales\POS\Infrastructure\Persistence\Repositories\DatabaseInvoiceRepository;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        $this->app->bind(InvoiceRepository::class, DatabaseInvoiceRepository::class);
        $this->app->bind(InvoiceHistoryReader::class, DatabaseInvoiceHistoryReader::class);
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
