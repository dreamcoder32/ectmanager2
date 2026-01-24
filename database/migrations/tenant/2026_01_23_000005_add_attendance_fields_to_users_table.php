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
        Schema::table('users', function (Blueprint $table) {
            $table->string('device_user_id')->nullable()->after('uid'); // User ID in ZKTeco device
            $table->boolean('attendance_enabled')->default(true)->after('is_active'); // Enable/disable attendance tracking
            $table->time('custom_work_start_time')->nullable()->after('attendance_enabled'); // Custom work start time for user
            $table->time('custom_work_end_time')->nullable()->after('custom_work_start_time'); // Custom work end time for user
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'device_user_id',
                'attendance_enabled',
                'custom_work_start_time',
                'custom_work_end_time',
            ]);
        });
    }
};
