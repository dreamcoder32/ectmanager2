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
            $table->string('sender_name')->nullable()->after('tracking_number');
            $table->string('sender_phone')->nullable()->after('sender_name');
            $table->text('sender_address')->nullable()->after('sender_phone');
            $table->string('receiver_name')->nullable()->after('sender_address');
            $table->string('receiver_phone')->nullable()->after('receiver_name');
            $table->text('receiver_address')->nullable()->after('receiver_phone');
            $table->decimal('weight', 8, 2)->default(0)->after('city_id');
            $table->decimal('declared_value', 10, 2)->default(0)->after('weight');
            $table->text('description')->nullable()->after('declared_value');
            $table->string('reference')->nullable()->after('description');
            $table->string('secondary_phone')->nullable()->after('reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parcels', function (Blueprint $table) {
            $table->dropColumn([
                'sender_name',
                'sender_phone', 
                'sender_address',
                'receiver_name',
                'receiver_phone',
                'receiver_address',
                'weight',
                'declared_value',
                'description',
                'reference',
                'secondary_phone'
            ]);
        });
    }
};
