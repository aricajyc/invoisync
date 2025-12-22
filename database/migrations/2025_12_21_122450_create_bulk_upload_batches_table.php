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
        Schema::create('bulk_upload_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('batch_reference')->unique();
            $table->string('original_filename');
            $table->string('file_path');
            $table->integer('total_records')->default(0);
            $table->integer('processed_records')->default(0);
            $table->integer('successful_records')->default(0);
            $table->integer('failed_records')->default(0);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])
                ->default('pending');
            $table->dateTime('upload_date');
            $table->dateTime('processing_started_at')->nullable();
            $table->dateTime('processing_completed_at')->nullable();
            $table->timestamps();
            
            // ==================== INDEXES ====================
            $table->index('user_id');
            $table->index('batch_reference');
            $table->index('status');
            $table->index('upload_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bulk_upload_batches');
    }
};
