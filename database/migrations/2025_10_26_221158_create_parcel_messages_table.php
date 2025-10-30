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
        Schema::create('parcel_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parcel_id')->constrained('parcels')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('message_type', ['outgoing', 'incoming']);
            $table->enum('message_status', ['sent', 'delivered', 'read', 'failed']);
            $table->text('message_content');
            $table->string('phone_number', 20);
            $table->timestamp('sent_at');
            $table->string('whatsapp_message_id')->nullable(); // For tracking WhatsApp message ID
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index(['parcel_id', 'sent_at']);
            $table->index(['user_id', 'sent_at']);
            $table->index('whatsapp_message_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parcel_messages');
    }
};