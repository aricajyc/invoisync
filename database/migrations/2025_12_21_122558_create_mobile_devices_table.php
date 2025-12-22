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
        Schema::create('mobile_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('device_token')->unique();
            $table->string('device_type', 50);
            $table->string('os_version', 20);
            $table->string('app_version', 20);
            $table->boolean('push_enabled')->default(true);
            $table->dateTime('last_active_at')->nullable();
            $table->dateTime('registered_at');
            $table->timestamps();
            
            // ==================== INDEXES ====================
            $table->index('user_id');
            $table->index('device_token');
            $table->index('push_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mobile_devices');
    }
};
