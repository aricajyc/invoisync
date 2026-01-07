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
            $table->string('supplier_country', 3)->default('MYS')->change();
            $table->string('buyer_country', 3)->default('MYS')->change();
            $table->string('shipping_country', 3)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('supplier_country', 2)->default('MY')->change();
            $table->string('buyer_country', 2)->default('MY')->change();
            $table->string('shipping_country', 2)->nullable()->change();
        });
    }
};
