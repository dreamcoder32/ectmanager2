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
        Schema::table('recoltes', function (Blueprint $table) {
            $table->decimal('manual_amount', 10, 2)->nullable()->after('company_id');
            $table->text('amount_discrepancy_note')->nullable()->after('manual_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recoltes', function (Blueprint $table) {
            $table->dropColumn(['manual_amount', 'amount_discrepancy_note']);
        });
    }
};
