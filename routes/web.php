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

Route::get('/setup-admin', function () {
    \App\Models\User::updateOrCreate(
        ['email' => 'admin@invoisync.com'],
        [
            'full_name' => 'System Administrator',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'phone_number' => '0000000000',
            'user_type' => 'Admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]
    );
    return 'Admin user created successfully! You can now login at /login';
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
    Route::post('/invoices/bulk-submit-myinvois', [App\Http\Controllers\InvoiceController::class, 'bulkSubmitToMyInvois'])->name('invoices.bulk-submit-myinvois');
    Route::post('/invoices/{invoice}/submit-myinvois', [App\Http\Controllers\InvoiceController::class, 'submitToMyInvois'])->name('invoices.submit-myinvois');
    Route::post('/invoices/{invoice}/sync-status', [App\Http\Controllers\InvoiceController::class, 'syncMyInvoisStatus'])->name('invoices.sync-status');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/activity-logs', [App\Http\Controllers\UserActivityController::class, 'index'])->name('activity-logs.index');
    Route::get('/business-profile', [BusinessProfileController::class, 'show'])->name('business-profile.show');
    Route::post('/business-profile', [BusinessProfileController::class, 'store'])->name('business-profile.store');
    Route::post('/business-profile/validate-tin', [BusinessProfileController::class, 'validateTin'])->name('business-profile.validate-tin');

    Route::prefix('ref')->group(function () {
        Route::get('/countries', [App\Http\Controllers\Api\ReferenceDataController::class, 'getCountries'])->name('ref.countries');
        Route::get('/states', [App\Http\Controllers\Api\ReferenceDataController::class, 'getStates'])->name('ref.states');
        Route::get('/msic-codes', [App\Http\Controllers\Api\ReferenceDataController::class, 'getMsicCodes'])->name('ref.msic-codes');
        Route::get('/unit-types', [App\Http\Controllers\Api\ReferenceDataController::class, 'getUnitTypes'])->name('ref.unit-types');
    });

    Route::middleware(['admin'])->prefix('admin')->group(function () {
        Route::get('/', [App\Http\Controllers\AdminController::class, 'index'])->name('admin.dashboard');
        Route::get('/users', [App\Http\Controllers\AdminController::class, 'users'])->name('admin.users');
        Route::get('/invoices', [App\Http\Controllers\AdminController::class, 'invoices'])->name('admin.invoices');
    });
});

require __DIR__.'/auth.php';
