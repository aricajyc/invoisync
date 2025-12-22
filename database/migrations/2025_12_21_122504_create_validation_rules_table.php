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
        Schema::create('validation_rules', function (Blueprint $table) {
            $table->id();
            $table->string('rule_code')->unique();
            $table->string('rule_name');
            $table->text('rule_description');
            $table->enum('rule_type', [
                'mandatory_field',
                'format_check',
                'business_logic',
                'compliance'
            ]);
            $table->text('validation_expression')->nullable();
            $table->text('error_message_template');
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);
            $table->timestamps();
            
            // ==================== INDEXES ====================
            $table->index('rule_code');
            $table->index('rule_type');
            $table->index('is_active');
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('validation_rules');
    }
};
