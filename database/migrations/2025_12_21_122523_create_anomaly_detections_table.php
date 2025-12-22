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
        Schema::create('anomaly_detections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->string('anomaly_type', 100);
            $table->decimal('anomaly_score', 5, 4);
            $table->text('anomaly_description');
            $table->string('detection_model', 100);
            $table->json('pattern_data')->nullable();
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->boolean('is_false_positive')->default(false);
            $table->text('resolution_notes')->nullable();
            $table->dateTime('detected_at');
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();
            
            // ==================== INDEXES ====================
            $table->index('invoice_id');
            $table->index('severity');
            $table->index('is_false_positive');
            $table->index('detected_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anomaly_detections');
    }
};
