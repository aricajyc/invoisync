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
        Schema::create('validation_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->foreignId('rule_id')->constrained('validation_rules')->onDelete('cascade');
            $table->enum('result_type', ['pass', 'fail', 'warning'])->default('pass');
            $table->text('validation_message')->nullable();
            $table->text('suggested_fix')->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->dateTime('validated_at');
            $table->timestamps();
            
            // ==================== INDEXES ====================
            $table->index('invoice_id');
            $table->index('rule_id');
            $table->index('result_type');
            $table->index('is_resolved');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('validation_results');
    }
};
