<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->date('birth_date')->nullable()->after('vehicle_info');
            $table->string('birth_place')->nullable()->after('birth_date');
            $table->string('address')->nullable()->after('birth_place');
            $table->date('contract_date')->nullable()->after('address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn(['birth_date', 'birth_place', 'address', 'contract_date']);
        });
    }
};