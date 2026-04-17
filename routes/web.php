<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\BusinessProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::post('/invoices/bulk-upload', [App\Http\Controllers\InvoiceController::class, 'bulkUpload'])->name('invoices.bulk-upload');
    Route::get('/invoices/bulk-review', [App\Http\Controllers\InvoiceController::class, 'bulkReview'])->name('invoices.bulk-review');
    Route::post('/invoices/bulk-commit', [App\Http\Controllers\InvoiceController::class, 'bulkCommit'])->name('invoices.bulk-commit');
    Route::resource('invoices', App\Http\Controllers\InvoiceController::class)->only(['index', 'create', 'store', 'edit', 'update']);
    Route::post('/invoices/detect-anomaly', [App\Http\Controllers\InvoiceController::class, 'detectAnomaly'])->name('invoices.detect-anomaly');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/activity-logs', [App\Http\Controllers\UserActivityController::class, 'index'])->name('activity-logs.index');
    Route::get('/business-profile', [BusinessProfileController::class, 'show'])->name('business-profile.show');
    Route::post('/business-profile', [BusinessProfileController::class, 'store'])->name('business-profile.store');

    Route::prefix('ref')->group(function () {
        Route::get('/countries', [App\Http\Controllers\Api\ReferenceDataController::class, 'getCountries'])->name('ref.countries');
        Route::get('/states', [App\Http\Controllers\Api\ReferenceDataController::class, 'getStates'])->name('ref.states');
        Route::get('/msic-codes', [App\Http\Controllers\Api\ReferenceDataController::class, 'getMsicCodes'])->name('ref.msic-codes');
        Route::get('/unit-types', [App\Http\Controllers\Api\ReferenceDataController::class, 'getUnitTypes'])->name('ref.unit-types');
    });
});

require __DIR__.'/auth.php';
