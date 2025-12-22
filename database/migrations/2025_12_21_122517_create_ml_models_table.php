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
        Schema::create('ml_models', function (Blueprint $table) {
            $table->id();
            $table->string('model_name')->unique();
            $table->enum('model_type', [
                'isolation_forest',
                'neural_network',
                'autoencoder'
            ]);
            $table->string('version', 20);
            $table->json('model_parameters')->nullable();
            $table->decimal('accuracy_score', 5, 4)->nullable();
            $table->dateTime('trained_at');
            $table->dateTime('last_used_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // ==================== INDEXES ====================
            $table->index('model_name');
            $table->index('model_type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ml_models');
    }
};
