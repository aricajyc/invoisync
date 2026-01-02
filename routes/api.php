<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\BulkUploadController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\BusinessProfileController;
use App\Http\Controllers\Api\InvoiceTemplateController;
use App\Http\Controllers\Api\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Protected routes
Route::middleware(['auth:sanctum'])->group(function () {
    
    // Authentication
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    
    // Dashboard
    Route::prefix('dashboard')->group(function () {
        Route::get('/overview', [DashboardController::class, 'overview']);
        Route::get('/analytics', [DashboardController::class, 'analytics']);
    });
    
    // Business Profile
    Route::prefix('business-profile')->group(function () {
        Route::get('/', [BusinessProfileController::class, 'show']);
        Route::post('/', [BusinessProfileController::class, 'createOrUpdate']);
        Route::delete('/', [BusinessProfileController::class, 'destroy']);
    });
    
    // Invoices
    Route::prefix('invoices')->group(function () {
        Route::get('/', [InvoiceController::class, 'index']);
        Route::post('/', [InvoiceController::class, 'store']);
        Route::get('/statistics', [InvoiceController::class, 'statistics']);
        Route::get('/{invoice}', [InvoiceController::class, 'show']);
        Route::put('/{invoice}', [InvoiceController::class, 'update']);
        Route::delete('/{invoice}', [InvoiceController::class, 'destroy']);
        
        // Invoice actions
        Route::post('/{invoice}/validate', [InvoiceController::class, 'validate']);
        Route::post('/{invoice}/submit', [InvoiceController::class, 'submitToMyInvois']);
        Route::post('/{invoice}/cancel', [InvoiceController::class, 'cancel']);
        Route::post('/{invoice}/duplicate', [InvoiceController::class, 'duplicate']);
        Route::get('/{invoice}/qr-code', [InvoiceController::class, 'getQrCode']);
        Route::get('/{invoice}/export-pdf', [InvoiceController::class, 'exportPdf']);
    });
    
    // Bulk Upload (B2B only)
    Route::prefix('bulk-upload')->middleware('user.type:B2B')->group(function () {
        Route::get('/', [BulkUploadController::class, 'index']);
        Route::post('/', [BulkUploadController::class, 'upload']);
        Route::get('/template', [BulkUploadController::class, 'downloadTemplate']);
        Route::get('/{batch}', [BulkUploadController::class, 'show']);
        Route::post('/{batch}/retry', [BulkUploadController::class, 'retry']);
        Route::get('/{batch}/error-report', [BulkUploadController::class, 'downloadErrorReport']);
    });
    
    // Invoice Templates (B2C)
    Route::prefix('invoice-templates')->group(function () {
        Route::get('/', [InvoiceTemplateController::class, 'index']);
        Route::post('/', [InvoiceTemplateController::class, 'store']);
        Route::get('/{template}', [InvoiceTemplateController::class, 'show']);
        Route::put('/{template}', [InvoiceTemplateController::class, 'update']);
        Route::delete('/{template}', [InvoiceTemplateController::class, 'destroy']);
        Route::post('/{template}/create-invoice', [InvoiceTemplateController::class, 'createInvoiceFromTemplate']);
    });
});

// API versioning (optional)
Route::prefix('v1')->group(function () {
    // Include all routes above
});