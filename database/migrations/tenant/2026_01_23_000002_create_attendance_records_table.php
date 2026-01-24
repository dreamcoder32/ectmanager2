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
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('device_id')->constrained('attendance_devices')->onDelete('cascade');
            $table->string('device_user_id'); // User ID in the ZKTeco device
            $table->timestamp('punch_time'); // Actual punch time from device
            $table->enum('punch_type', ['check_in', 'check_out', 'break_start', 'break_end'])->default('check_in');
            $table->enum('verify_mode', ['password', 'fingerprint', 'face', 'card'])->nullable();
            $table->string('work_code')->nullable(); // Work code if any
            $table->text('notes')->nullable();
            $table->boolean('is_synced')->default(false); // Whether synced from device
            $table->timestamps();

            // Indexes for better query performance
            $table->index(['user_id', 'punch_time']);
            $table->index(['device_id', 'punch_time']);
            $table->index('punch_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
