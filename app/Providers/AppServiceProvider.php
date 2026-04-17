<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Login;
use App\Models\UserActivity;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
        
        \Illuminate\Validation\Rules\Password::defaults(function () {
            return \Illuminate\Validation\Rules\Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised();
        });

        Event::listen(function (Login $event) {
            UserActivity::create([
                'user_id' => $event->user->id,
                'action' => 'Login',
                'description' => 'User logged in to the application',
                'ip_address' => request()->ip(),
            ]);
        });
    }

    protected $policies = [
    Invoice::class => InvoicePolicy::class,
    BulkUploadBatch::class => BulkUploadBatchPolicy::class,
    InvoiceTemplate::class => InvoiceTemplatePolicy::class,
];
}
