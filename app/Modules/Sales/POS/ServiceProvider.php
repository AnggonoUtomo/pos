<?php

namespace App\Modules\Sales\POS;

use App\Modules\Sales\POS\Application\Contracts\ActiveShiftLookup;
use App\Modules\Sales\POS\Application\Contracts\CashierShiftRepository;
use App\Modules\Sales\POS\Application\Contracts\HeldCartRepository;
use App\Modules\Sales\POS\Application\Contracts\InvoiceHistoryReader;
use App\Modules\Sales\POS\Application\Contracts\InvoiceRepository;
use App\Modules\Sales\POS\Application\Contracts\ReturnInvoiceLookup;
use App\Modules\Sales\POS\Application\Contracts\SalesReportReader;
use App\Modules\Sales\POS\Infrastructure\Persistence\Repositories\DatabaseCashierShiftRepository;
use App\Modules\Sales\POS\Infrastructure\Persistence\Repositories\DatabaseHeldCartRepository;
use App\Modules\Sales\POS\Infrastructure\Persistence\Repositories\DatabaseInvoiceHistoryReader;
use App\Modules\Sales\POS\Infrastructure\Persistence\Repositories\DatabaseInvoiceRepository;
use App\Modules\Sales\POS\Infrastructure\Persistence\Repositories\DatabaseReturnInvoiceLookup;
use App\Modules\Sales\POS\Infrastructure\Persistence\Repositories\DatabaseSalesReportReader;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CashierShiftRepository::class, DatabaseCashierShiftRepository::class);
        $this->app->bind(ActiveShiftLookup::class, DatabaseCashierShiftRepository::class);
        $this->app->bind(HeldCartRepository::class, DatabaseHeldCartRepository::class);
        $this->app->bind(SalesReportReader::class, DatabaseSalesReportReader::class);
        $this->app->bind(InvoiceRepository::class, DatabaseInvoiceRepository::class);
        $this->app->bind(InvoiceHistoryReader::class, DatabaseInvoiceHistoryReader::class);
        $this->app->bind(ReturnInvoiceLookup::class, DatabaseReturnInvoiceLookup::class);
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
