<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE invoices MODIFY COLUMN status ENUM('draft', 'validated', 'submitted', 'approved', 'rejected', 'cancelled', 'invalid') DEFAULT 'draft'");
        DB::statement("ALTER TABLE myinvois_submissions MODIFY COLUMN status ENUM('pending', 'submitted', 'accepted', 'rejected', 'invalid') DEFAULT 'pending'");
        
        // Also update existing 'rejected' invoices to 'invalid' if they have a myinvois_uid (meaning they were rejected by LHDN validation)
        DB::statement("UPDATE invoices SET status = 'invalid' WHERE status = 'rejected' AND myinvois_uid IS NOT NULL");
        DB::statement("UPDATE myinvois_submissions SET status = 'invalid' WHERE status = 'rejected' AND myinvois_uid IS NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverting enums in MySQL is complex if there's data using the new value. 
        // We will just change the data back and leave the enum type as is, or alter it back if strictly required.
        DB::statement("UPDATE invoices SET status = 'rejected' WHERE status = 'invalid'");
        DB::statement("UPDATE myinvois_submissions SET status = 'rejected' WHERE status = 'invalid'");
        
        DB::statement("ALTER TABLE invoices MODIFY COLUMN status ENUM('draft', 'validated', 'submitted', 'approved', 'rejected', 'cancelled') DEFAULT 'draft'");
        DB::statement("ALTER TABLE myinvois_submissions MODIFY COLUMN status ENUM('pending', 'submitted', 'accepted', 'rejected') DEFAULT 'pending'");
    }
};
