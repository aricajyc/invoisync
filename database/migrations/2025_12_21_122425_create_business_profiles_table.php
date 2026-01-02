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
            $table->string('sst_registration_number')->unique()->comment('SST from IRBM');
            $table->string('tourism_tax_registration_number')->unique()->comment('Tourism Tax');
            $table->string('msic_code', 10);
            $table->string('business_activity_description', 300);
            $table->string('address_line_0', 150);
            $table->string('address_line_1', 150);
            $table->string('address_line_2', 150);
            $table->string('postal_zone', 50);
            $table->string('city', 50);
            $table->string('state', 100);
            $table->string('country', 3)->default('MYS');
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
