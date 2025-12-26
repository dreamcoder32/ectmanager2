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
        Schema::create('call_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parcel_id')->constrained('parcels')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('call_type', ['outgoing', 'incoming']);
            $table->enum('call_status', ['answered', 'no_answer', 'busy', 'failed']);
            $table->text('notes')->nullable();
            $table->timestamp('called_at');
            $table->integer('duration_seconds')->default(0);
            $table->timestamps();
            
            $table->index(['parcel_id', 'called_at']);
            $table->index(['user_id', 'called_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_logs');
    }
};
