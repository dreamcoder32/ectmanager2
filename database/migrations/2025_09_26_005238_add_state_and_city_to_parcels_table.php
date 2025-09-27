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
            $table->foreignId('state_id')->nullable()->constrained('states')->onDelete('set null');
            $table->foreignId('city_id')->nullable()->constrained('cities')->onDelete('set null');
            
            // Add indexes for performance
            $table->index('state_id');
            $table->index('city_id');
            $table->index(['state_id', 'city_id']); // Composite index for state-city queries
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parcels', function (Blueprint $table) {
            $table->dropForeign(['state_id']);
            $table->dropForeign(['city_id']);
            $table->dropIndex(['parcels_state_id_index']);
            $table->dropIndex(['parcels_city_id_index']);
            $table->dropIndex(['parcels_state_id_city_id_index']);
            $table->dropColumn(['state_id', 'city_id']);
        });
    }
};
