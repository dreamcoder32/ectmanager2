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
        Schema::create('attendance_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('date'); // Date of attendance
            $table->time('first_check_in')->nullable();
            $table->time('last_check_out')->nullable();
            $table->integer('total_work_minutes')->default(0); // Total minutes worked
            $table->integer('total_break_minutes')->default(0); // Total break minutes
            $table->integer('overtime_minutes')->default(0); // Overtime minutes
            $table->integer('late_minutes')->default(0); // Late arrival minutes
            $table->integer('early_departure_minutes')->default(0); // Early departure minutes
            $table->enum('status', ['present', 'absent', 'half_day', 'leave', 'holiday'])->default('absent');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Unique constraint to prevent duplicate summaries
            $table->unique(['user_id', 'date']);

            // Indexes
            $table->index(['user_id', 'date']);
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_summaries');
    }
};
