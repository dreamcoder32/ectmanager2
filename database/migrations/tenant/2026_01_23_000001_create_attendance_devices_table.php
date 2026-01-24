<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendance_devices', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Device name (e.g., "Main Office K60 Pro")
            $table->string('device_model')->default('K60 Pro'); // Device model
            $table->string('serial_number')->unique(); // Device serial number
            $table->string('ip_address'); // Device IP address
            $table->integer('port')->default(4370); // Device port
            $table->string('location')->nullable(); // Physical location
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sync_at')->nullable(); // Last successful sync
            $table->text('sync_error')->nullable(); // Last sync error if any
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_devices');
    }
};
