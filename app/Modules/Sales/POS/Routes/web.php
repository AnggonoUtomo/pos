<?php

use App\Modules\Sales\POS\Presentation\Controllers\InvoiceController;
use App\Modules\Sales\POS\Presentation\Controllers\POSController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->group(function () {
    Route::middleware(['web', 'auth'])->group(function (): void {
        Route::get('/pos', [POSController::class, 'index'])->name('pos.index');
        Route::get('/pos/catalog', [POSController::class, 'catalog'])->name('pos.catalog');
        Route::post('/pos/quote', [POSController::class, 'quote'])->name('pos.quote');
        Route::get('/pos/payment-methods', [InvoiceController::class, 'methods'])->name('pos.payment-methods');
        Route::post('/pos/invoices', [InvoiceController::class, 'store'])->name('pos.invoices.store');
        Route::get('/pos/invoices/{id}', [InvoiceController::class, 'show'])->whereUlid('id')->name('pos.invoices.show');
    });
});
