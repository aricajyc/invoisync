<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('submission_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('analytics_date');
            $table->integer('total_invoices')->default(0);
            $table->integer('successful_submissions')->default(0);
            $table->integer('failed_submissions')->default(0);
            $table->integer('pending_submissions')->default(0);
            $table->decimal('total_invoice_value', 15, 2)->default(0);
            $table->decimal('average_processing_time', 10, 2)->nullable()->comment('In seconds');
            $table->integer('validation_errors_count')->default(0);
            $table->integer('anomalies_detected_count')->default(0);
            $table->timestamps();
            
            // ==================== INDEXES ====================
            $table->index('user_id');
            $table->index('analytics_date');
            $table->unique(['user_id', 'analytics_date'], 'unique_analytics_per_user_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submission_analytics');
    }
};
