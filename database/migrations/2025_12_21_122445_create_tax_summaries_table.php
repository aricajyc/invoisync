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
        Schema::create('tax_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->string('tax_type', 2)
                ->comment('01-Sales, 02-Service, 03-Tourism, 04-HRD, 05-WHT, 06-Others');
            $table->decimal('taxable_amount', 15, 2)->comment('Amount subject to tax');
            $table->decimal('tax_rate', 5, 2)->comment('Percentage');
            $table->decimal('tax_amount', 15, 2)->comment('Total tax for this type');
            $table->decimal('tax_exempted_amount', 15, 2)->nullable()->comment('If applicable');
            $table->timestamps();
            
            // ==================== INDEXES ====================
            $table->index('invoice_id');
            $table->index('tax_type');
            $table->unique(['invoice_id', 'tax_type'], 'unique_tax_per_invoice');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_summaries');
    }
};
