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
    Route::resource('invoices', App\Http\Controllers\InvoiceController::class)->only(['index', 'create']);
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/business-profile', [BusinessProfileController::class, 'show'])->name('business-profile.show');
    Route::post('/business-profile', [BusinessProfileController::class, 'store'])->name('business-profile.store');

    Route::prefix('ref')->group(function () {
        Route::get('/countries', [App\Http\Controllers\Api\ReferenceDataController::class, 'getCountries'])->name('ref.countries');
        Route::get('/states', [App\Http\Controllers\Api\ReferenceDataController::class, 'getStates'])->name('ref.states');
        Route::get('/msic-codes', [App\Http\Controllers\Api\ReferenceDataController::class, 'getMsicCodes'])->name('ref.msic-codes');
    });
});

require __DIR__.'/auth.php';
