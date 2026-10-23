<?php

namespace App\Modules\Finance\Payments;

use App\Modules\Finance\Payments\Application\Actions\RecordPurchasePayment;
use App\Modules\Finance\Payments\Application\Actions\RecordPurchaseRefundReceipt;
use App\Modules\Finance\Payments\Application\Actions\RecordSalePayment;
use App\Modules\Finance\Payments\Application\Actions\RecordSaleRefundPayment;
use App\Modules\Finance\Payments\Application\Contracts\DisbursementRepository;
use App\Modules\Finance\Payments\Application\Contracts\PaymentHistoryReader;
use App\Modules\Finance\Payments\Application\Contracts\PaymentsRepository;
use App\Modules\Finance\Payments\Application\Contracts\PurchasePaymentMethods;
use App\Modules\Finance\Payments\Application\Contracts\PurchasePaymentProofLookup;
use App\Modules\Finance\Payments\Application\Contracts\PurchasePaymentWriter;
use App\Modules\Finance\Payments\Application\Contracts\PurchaseRefundReceiptMethods;
use App\Modules\Finance\Payments\Application\Contracts\PurchaseRefundReceiptRepository;
use App\Modules\Finance\Payments\Application\Contracts\PurchaseRefundReceiptWriter;
use App\Modules\Finance\Payments\Application\Contracts\RefundRepository;
use App\Modules\Finance\Payments\Application\Contracts\SalePaymentMethods;
use App\Modules\Finance\Payments\Application\Contracts\SalePaymentWriter;
use App\Modules\Finance\Payments\Application\Contracts\SaleRefundPaymentMethods;
use App\Modules\Finance\Payments\Application\Contracts\SaleRefundPaymentWriter;
use App\Modules\Finance\Payments\Infrastructure\Persistence\Repositories\DatabasePaymentHistoryReader;
use App\Modules\Finance\Payments\Infrastructure\Persistence\Repositories\DatabasePurchasePaymentProofLookup;
use App\Modules\Finance\Payments\Infrastructure\Persistence\Repositories\DatabaseSalePaymentMethods;
use App\Modules\Finance\Payments\Infrastructure\Persistence\Repositories\EloquentDisbursementRepository;
use App\Modules\Finance\Payments\Infrastructure\Persistence\Repositories\EloquentPaymentsRepository;
use App\Modules\Finance\Payments\Infrastructure\Persistence\Repositories\EloquentPurchaseRefundReceiptRepository;
use App\Modules\Finance\Payments\Infrastructure\Persistence\Repositories\EloquentRefundRepository;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PurchaseRefundReceiptWriter::class, RecordPurchaseRefundReceipt::class);
        $this->app->bind(PurchaseRefundReceiptRepository::class, EloquentPurchaseRefundReceiptRepository::class);
        $this->app->bind(PurchaseRefundReceiptMethods::class, DatabaseSalePaymentMethods::class);
        $this->app->bind(PurchasePaymentProofLookup::class, DatabasePurchasePaymentProofLookup::class);
        $this->app->bind(SaleRefundPaymentWriter::class, RecordSaleRefundPayment::class);
        $this->app->bind(SaleRefundPaymentMethods::class, DatabaseSalePaymentMethods::class);
        $this->app->bind(RefundRepository::class, EloquentRefundRepository::class);
        $this->app->bind(PaymentHistoryReader::class, DatabasePaymentHistoryReader::class);
        $this->app->bind(PurchasePaymentWriter::class, RecordPurchasePayment::class);
        $this->app->bind(PurchasePaymentMethods::class, DatabaseSalePaymentMethods::class);
        $this->app->bind(DisbursementRepository::class, EloquentDisbursementRepository::class);
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
