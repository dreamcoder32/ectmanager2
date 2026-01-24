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
        Schema::create('attendance_settings', function (Blueprint $table) {
            $table->id();
            $table->time('work_start_time')->default('09:00:00'); // Standard work start time
            $table->time('work_end_time')->default('17:00:00'); // Standard work end time
            $table->integer('standard_work_hours')->default(8); // Standard work hours per day
            $table->integer('grace_period_minutes')->default(15); // Grace period for late arrival
            $table->integer('half_day_hours')->default(4); // Minimum hours for half day
            $table->boolean('auto_checkout_enabled')->default(false); // Auto checkout at end of day
            $table->time('auto_checkout_time')->nullable();
            $table->integer('sync_interval_minutes')->default(30); // Device sync interval
            $table->json('working_days')->nullable(); // Working days (removed default to avoid MySQL error)
            $table->timestamps();
        });

        // Insert default settings
        DB::table('attendance_settings')->insert([
            'work_start_time' => '09:00:00',
            'work_end_time' => '17:00:00',
            'standard_work_hours' => 8,
            'grace_period_minutes' => 15,
            'half_day_hours' => 4,
            'auto_checkout_enabled' => false,
            'sync_interval_minutes' => 30,
            'working_days' => json_encode(['monday', 'tuesday', 'wednesday', 'thursday', 'friday']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_settings');
    }
};
