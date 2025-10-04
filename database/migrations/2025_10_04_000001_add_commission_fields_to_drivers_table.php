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
        Schema::table('drivers', function (Blueprint $table) {
            // Commission Configuration
            $table->decimal('commission_rate', 5, 2)->default(0); // Percentage (0-100)
            $table->enum('commission_type', ['percentage', 'fixed_per_parcel'])->default('percentage');
            $table->boolean('commission_is_active')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn([
                'commission_rate',
                'commission_type',
                'commission_is_active'
            ]);
        });
    }
};