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
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('supplier_registration_type')->nullable()->after('supplier_tin')->comment('BRN, NRIC, PASSPORT, ARMY');
            $table->string('buyer_registration_type')->nullable()->after('buyer_tin')->comment('BRN, NRIC, PASSPORT, ARMY');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['supplier_registration_type', 'buyer_registration_type']);
        });
    }
};
