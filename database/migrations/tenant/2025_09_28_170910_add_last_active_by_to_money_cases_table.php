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
        Schema::table('money_cases', function (Blueprint $table) {
            $table->foreignId('last_active_by')->nullable()->constrained('users')->onDelete('set null')->after('status');
            $table->timestamp('last_activated_at')->nullable()->after('last_active_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('money_cases', function (Blueprint $table) {
            $table->dropForeign(['last_active_by']);
            $table->dropColumn(['last_active_by', 'last_activated_at']);
        });
    }
};
