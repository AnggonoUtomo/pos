<?php

namespace App\Modules\Finance\Payments;

use App\Modules\Finance\Payments\Application\Actions\RecordSalePayment;
use App\Modules\Finance\Payments\Application\Contracts\PaymentsRepository;
use App\Modules\Finance\Payments\Application\Contracts\SalePaymentMethods;
use App\Modules\Finance\Payments\Application\Contracts\SalePaymentWriter;
use App\Modules\Finance\Payments\Infrastructure\Persistence\Repositories\DatabaseSalePaymentMethods;
use App\Modules\Finance\Payments\Infrastructure\Persistence\Repositories\EloquentPaymentsRepository;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SalePaymentWriter::class, RecordSalePayment::class);
        $this->app->bind(SalePaymentMethods::class, DatabaseSalePaymentMethods::class);
        $this->app->bind(PaymentsRepository::class,
            EloquentPaymentsRepository::class);
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
