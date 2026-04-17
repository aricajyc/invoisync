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
        Schema::table('business_profiles', function (Blueprint $table) {
            $table->string('sst_registration_number')->nullable()->change();
            $table->string('tourism_tax_registration_number')->nullable()->change();
            $table->string('address_line_1')->nullable()->change();
            $table->string('address_line_2')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            $table->string('sst_registration_number')->nullable(false)->change();
            $table->string('tourism_tax_registration_number')->nullable(false)->change();
            $table->string('address_line_1')->nullable(false)->change();
            $table->string('address_line_2')->nullable(false)->change();
        });
    }
};
