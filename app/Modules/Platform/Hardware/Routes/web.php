<?php

use App\Modules\Platform\Hardware\Presentation\Controllers\ScannerController;
use App\Modules\Platform\Hardware\Presentation\Middleware\EnsureVirtualHardwareEnabled;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', EnsureVirtualHardwareEnabled::class, 'auth', 'verified'])
    ->prefix('platform/hardware/scanner')->name('platform.hardware.scanner.')->group(function (): void {
        Route::get('/', [ScannerController::class, 'index'])->name('index');
        Route::get('/resolve', [ScannerController::class, 'resolve'])->middleware('throttle:120,1')->name('resolve');
    });
