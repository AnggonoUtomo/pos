<?php

use App\Modules\Sales\Returns\Presentation\Controllers\ReturnDraftController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified'])->prefix('sales/returns')->name('sales.returns.')->group(function (): void {
    Route::get('data', [ReturnDraftController::class, 'data'])->name('data');
    Route::get('invoices', [ReturnDraftController::class, 'invoices'])->name('invoices');
    Route::get('invoices/{id}', [ReturnDraftController::class, 'invoice'])->whereUlid('id')->name('invoice');
    Route::get('/', [ReturnDraftController::class, 'index'])->name('index');
    Route::post('/', [ReturnDraftController::class, 'store'])->name('store');
    Route::put('{id}', [ReturnDraftController::class, 'update'])->whereUlid('id')->name('update');
    Route::delete('{id}', [ReturnDraftController::class, 'destroy'])->whereUlid('id')->name('destroy');
    Route::post('{id}/restore', [ReturnDraftController::class, 'restore'])->whereUlid('id')->name('restore');
    Route::post('{id}/post', [ReturnDraftController::class, 'post'])->whereUlid('id')->name('post');
});
