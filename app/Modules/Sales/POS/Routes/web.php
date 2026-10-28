<?php

use App\Modules\Sales\POS\Presentation\Controllers\CashierShiftController;
use App\Modules\Sales\POS\Presentation\Controllers\HeldCartController;
use App\Modules\Sales\POS\Presentation\Controllers\InvoiceController;
use App\Modules\Sales\POS\Presentation\Controllers\InvoiceHistoryController;
use App\Modules\Sales\POS\Presentation\Controllers\POSController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->group(function () {
    Route::middleware(['web', 'auth'])->group(function (): void {
        Route::get('/sales/cashier-shifts', [CashierShiftController::class, 'index'])->name('sales.cashier-shifts.index');
        Route::get('/pos/shifts/current', [CashierShiftController::class, 'current'])->name('pos.shifts.current');
        Route::get('/pos/shifts/drawers/{id}', [CashierShiftController::class, 'drawer'])->whereUlid('id')->name('pos.shifts.drawer');
        Route::get('/pos/shifts', [CashierShiftController::class, 'data'])->name('pos.shifts.data');
        Route::get('/pos/shifts/{id}', [CashierShiftController::class, 'show'])->whereUlid('id')->name('pos.shifts.show');
        Route::post('/pos/shifts/{id}/open', [CashierShiftController::class, 'open'])->whereUlid('id')->name('pos.shifts.open');
        Route::post('/pos/shifts/{id}/close', [CashierShiftController::class, 'close'])->whereUlid('id')->name('pos.shifts.close');
        Route::post('/pos/shifts/{id}/review', [CashierShiftController::class, 'review'])->whereUlid('id')->name('pos.shifts.review');
        Route::get('/sales/invoices', [InvoiceHistoryController::class, 'index'])->name('sales.invoices.index');
        Route::get('/sales/invoices/data', [InvoiceHistoryController::class, 'data'])->name('sales.invoices.data');
        Route::get('/pos', [POSController::class, 'index'])->name('pos.index');
        Route::get('/pos/held-carts', [HeldCartController::class, 'index'])->name('pos.held-carts.index');
        Route::get('/pos/held-carts/{id}', [HeldCartController::class, 'show'])->whereUlid('id')->name('pos.held-carts.show');
        Route::post('/pos/held-carts/{id}', [HeldCartController::class, 'store'])->whereUlid('id')->name('pos.held-carts.store');
        Route::post('/pos/held-carts/{id}/cancel', [HeldCartController::class, 'cancel'])->whereUlid('id')->name('pos.held-carts.cancel');
        Route::get('/pos/catalog', [POSController::class, 'catalog'])->name('pos.catalog');
        Route::get('/pos/scan', [POSController::class, 'scan'])->name('pos.scan');
        Route::post('/pos/quote', [POSController::class, 'quote'])->name('pos.quote');
        Route::get('/pos/payment-methods', [InvoiceController::class, 'methods'])->name('pos.payment-methods');
        Route::post('/pos/invoices', [InvoiceController::class, 'store'])->name('pos.invoices.store');
        Route::get('/pos/invoices/{id}', [InvoiceController::class, 'show'])->whereUlid('id')->name('pos.invoices.show');
    });
});
