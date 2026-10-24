<?php

use App\Modules\Platform\CompanySettings\Presentation\Controllers\CompanySettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified'])->prefix('platform/company-settings')->name('platform.company-settings.')->group(function () {
    Route::get('/', [CompanySettingsController::class, 'index'])->name('index');
    Route::put('/', [CompanySettingsController::class, 'update'])->name('update');
});
