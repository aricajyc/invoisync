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
        Schema::create('myinvois_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->string('submission_reference')->unique();
            $table->enum('submission_type', ['single', 'bulk'])->default('single');
            $table->json('request_payload');
            $table->json('response_payload')->nullable();
            $table->string('myinvois_uid')->nullable();
            $table->text('qr_code_url')->nullable();
            $table->enum('status', ['pending', 'submitted', 'accepted', 'rejected'])
                ->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->integer('retry_count')->default(0);
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('response_received_at')->nullable();
            $table->timestamps();
            
            // ==================== INDEXES ====================
            $table->index('invoice_id');
            $table->index('submission_reference');
            $table->index('status');
            $table->index('submitted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('myinvois_submissions');
    }
};
