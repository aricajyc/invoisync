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
            $table->renameColumn('supplier_address_line1', 'supplier_address_line0');
            $table->renameColumn('supplier_address_line2', 'supplier_address_line1');
            $table->renameColumn('supplier_address_line3', 'supplier_address_line2');
            
            $table->renameColumn('buyer_address_line1', 'buyer_address_line0');
            $table->renameColumn('buyer_address_line2', 'buyer_address_line1');
            $table->renameColumn('buyer_address_line3', 'buyer_address_line2');
            
            $table->renameColumn('shipping_address_line1', 'shipping_address_line0');
            $table->renameColumn('shipping_address_line2', 'shipping_address_line1');
            $table->renameColumn('shipping_address_line3', 'shipping_address_line2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->renameColumn('supplier_address_line0', 'supplier_address_line1');
            $table->renameColumn('supplier_address_line1', 'supplier_address_line2');
            $table->renameColumn('supplier_address_line2', 'supplier_address_line3');
            
            $table->renameColumn('buyer_address_line0', 'buyer_address_line1');
            $table->renameColumn('buyer_address_line1', 'buyer_address_line2');
            $table->renameColumn('buyer_address_line2', 'buyer_address_line3');
            
            $table->renameColumn('shipping_address_line0', 'shipping_address_line1');
            $table->renameColumn('shipping_address_line1', 'shipping_address_line2');
            $table->renameColumn('shipping_address_line2', 'shipping_address_line3');
        });
    }
};
