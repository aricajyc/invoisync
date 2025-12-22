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
        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')
                ->constrained('myinvois_submissions')
                ->onDelete('cascade');
            $table->string('endpoint');
            $table->string('http_method', 10);
            $table->integer('http_status_code')->nullable();
            $table->json('request_headers')->nullable();
            $table->json('request_body')->nullable();
            $table->json('response_headers')->nullable();
            $table->json('response_body')->nullable();
            $table->decimal('response_time_ms', 10, 2)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            // ==================== INDEXES ====================
            $table->index('submission_id');
            $table->index('http_status_code');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_logs');
    }
};
