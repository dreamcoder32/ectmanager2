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
            $table->foreignId('agent_id')->nullable()->after('driver_id')->constrained('users')->onDelete('set null');
            $table->decimal('total_amount', 12, 2)->default(0)->after('total_cod_amount'); // For commission calculations
            
            // Add index for agent queries
            $table->index(['agent_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->dropForeign(['agent_id']);
            $table->dropIndex(['agent_id', 'status']);
            $table->dropColumn(['agent_id', 'total_amount']);
        });
    }
};
