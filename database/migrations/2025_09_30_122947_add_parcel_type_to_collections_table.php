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
        Schema::table('collections', function (Blueprint $table) {
            $table->enum('parcel_type', ['stopdesk', 'home_delivery'])
                  ->default('stopdesk')
                  ->after('collected_at')
                  ->comment('Type of parcel collection: stopdesk or home_delivery');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->dropColumn('parcel_type');
        });
    }
};
