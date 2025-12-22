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
        Schema::create('business_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('business_name');
            $table->string('business_registration_number')->unique();
            $table->string('tax_identification_number')->unique()->comment('TIN from IRBM');
            $table->string('business_type', 100);
            $table->text('business_address');
            $table->string('city', 100);
            $table->string('state', 100);
            $table->string('postal_code', 20);
            $table->string('country', 2)->default('MY')->comment('ISO 3166-1 alpha-2');
            $table->string('contact_email');
            $table->string('contact_phone', 20);
            $table->timestamps();
            
            // Indexes
            $table->index('user_id');
            $table->unique('business_registration_number', 'idx_brn');
            $table->unique('tax_identification_number', 'idx_tin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_profiles');
    }
};
