<?php

use App\Modules\Finance\Payments\Presentation\Controllers\CashbookController;
use App\Modules\Finance\Payments\Presentation\Controllers\PaymentAccountController;
use App\Modules\Finance\Payments\Presentation\Controllers\PaymentHistoryController;
use App\Modules\Finance\Payments\Presentation\Controllers\PaymentMethodController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->group(function () {
    Route::middleware(['auth', 'verified'])->prefix('finance/cashbook')->name('finance.cashbook.')->group(function (): void {
        $controller = CashbookController::class;
        Route::get('/', [$controller, 'index'])->name('index');
        Route::get('data', [$controller, 'data'])->name('data');
        Route::post('openings', [$controller, 'open'])->name('open');
        Route::post('entries', [$controller, 'store'])->name('store');
        Route::post('entries/{id}/reverse', [$controller, 'reverse'])->whereUlid('id')->name('reverse');
    });
    Route::middleware(['auth', 'verified'])->prefix('finance/payments/history')->name('finance.payments.history.')->group(function (): void {
        $controller = PaymentHistoryController::class;
        Route::get('data', [$controller, 'data'])->name('data');
        Route::get('/', [$controller, 'index'])->name('index');
        Route::get('refunds/{id}', [$controller, 'refund'])->whereUlid('id')->name('refund');
        Route::get('supplier-refunds/{id}', [$controller, 'supplierRefund'])->whereUlid('id')->name('supplier-refund');
        Route::get('{direction}/{id}', [$controller, 'show'])->whereIn('direction', ['incoming', 'outgoing'])->whereUlid('id')->name('show');
    });
    foreach (['payment-accounts' => PaymentAccountController::class, 'payment-methods' => PaymentMethodController::class] as $path => $controller) {
        Route::middleware(['web', 'auth', 'verified'])->prefix('finance/'.$path)->name('finance.'.$path.'.')->group(function () use ($controller): void {
            Route::get('data', [$controller, 'data'])->name('data');
            Route::get('/', [$controller, 'index'])->name('index');
            Route::post('/', [$controller, 'store'])->name('store');
            Route::put('{id}', [$controller, 'update'])->whereUlid('id')->name('update');
            Route::delete('{id}', [$controller, 'destroy'])->whereUlid('id')->name('destroy');
            Route::post('{id}/restore', [$controller, 'restore'])->whereUlid('id')->name('restore');
        });
    }
});
