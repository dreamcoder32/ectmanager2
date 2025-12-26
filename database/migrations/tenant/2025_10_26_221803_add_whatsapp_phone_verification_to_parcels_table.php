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
        Schema::table('parcels', function (Blueprint $table) {
            $table->boolean('recipient_phone_whatsapp')->nullable()->after('has_whatsapp_tag');
            $table->boolean('secondary_phone_whatsapp')->nullable()->after('recipient_phone_whatsapp');
            $table->timestamp('whatsapp_verified_at')->nullable()->after('secondary_phone_whatsapp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parcels', function (Blueprint $table) {
            $table->dropColumn([
                'recipient_phone_whatsapp',
                'secondary_phone_whatsapp',
                'whatsapp_verified_at'
            ]);
        });
    }
};