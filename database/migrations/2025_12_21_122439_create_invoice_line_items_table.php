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
        Schema::create('invoice_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->integer('line_number')->comment('Sequential classification number');
            
            // ==================== PRODUCT/SERVICE CLASSIFICATION (MANDATORY) ====================
            $table->string('classification_code', 10)
                ->comment('001-999999, 003-SST exempt, 004-zero-rated, etc');
            $table->text('product_service_description')->comment('MANDATORY');
            
            // ==================== QUANTITY AND PRICING (MANDATORY) ====================
            $table->decimal('quantity', 15, 4);
            $table->string('unit_of_measure', 10)->comment('C62-unit, etc per UN/ECE Rec 20');
            $table->decimal('unit_price', 15, 2)->comment('Excluding tax');
            
            // ==================== SUBTOTAL (MANDATORY) ====================
            $table->decimal('subtotal', 15, 2)->comment('Before discount/tax');
            
            // ==================== DISCOUNT (OPTIONAL) ====================
            $table->decimal('discount_rate', 5, 2)->nullable()->comment('Percentage');
            $table->decimal('discount_amount', 15, 2)->nullable()->comment('Fixed amount');
            
            // ==================== TAX INFORMATION (MANDATORY) ====================
            $table->string('tax_type', 2)
                ->comment('01-Sales Tax, 02-Service Tax, 03-Tourism Tax, 04-HRD, 05-WHT, 06-Others');
            $table->decimal('tax_rate', 5, 2)->comment('Percentage, can be 0.00');
            $table->decimal('tax_amount', 15, 2)->comment('Calculated tax');
            $table->enum('tax_exemption_reason', [
                '01', '02', '03', '04', '05', '06', '07'
            ])->nullable()->comment('If tax_rate = 0');
            
            // ==================== DETAILS OF TAX EXEMPTION (IF APPLICABLE) ====================
            $table->decimal('tax_exempted_amount', 15, 2)->nullable();
            
            // ==================== CHARGE/FEE AT LINE LEVEL (OPTIONAL) ====================
            $table->decimal('charge_fee_amount', 15, 2)->nullable();
            
            // ==================== COUNTRY OF ORIGIN (FOR GOODS) ====================
            $table->string('country_of_origin', 2)->nullable()->comment('ISO 3166-1 alpha-2');
            
            // ==================== PRODUCT TARIFF CODE (OPTIONAL - ANNEXURE) ====================
            $table->string('product_tariff_code', 20)->nullable()->comment('HS code for imports/exports');
            
            // ==================== MEASUREMENTS (CALCULATED) ====================
            $table->decimal('total_excluding_tax_per_line', 15, 2);
            $table->decimal('total_including_tax_per_line', 15, 2);
            
            $table->timestamps();
            
            // ==================== INDEXES ====================
            $table->index('invoice_id');
            $table->index('line_number');
            $table->index('classification_code');
            $table->index('tax_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_line_items');
    }
};
