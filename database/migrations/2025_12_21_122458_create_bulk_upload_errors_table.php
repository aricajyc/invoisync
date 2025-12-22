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
        Schema::create('bulk_upload_errors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')
                ->constrained('bulk_upload_batches')
                ->onDelete('cascade');
            $table->integer('row_number');
            $table->string('field_name')->nullable();
            $table->string('error_type', 100);
            $table->text('error_message');
            $table->text('suggested_correction')->nullable();
            $table->enum('severity', ['critical', 'warning', 'info'])->default('critical');
            $table->boolean('is_resolved')->default(false);
            $table->timestamps();
            
            // ==================== INDEXES ====================
            $table->index('batch_id');
            $table->index('severity');
            $table->index('is_resolved');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bulk_upload_errors');
    }
};
