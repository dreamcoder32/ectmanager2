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
        Schema::table('users', function (Blueprint $table) {
            // Salary Configuration
            $table->decimal('salary_amount', 10, 2)->default(0);
            $table->string('salary_currency', 3)->default('DZD');
            $table->integer('salary_payment_day')->default(1); // Day of month (1-31)
            $table->date('salary_start_date')->nullable();
            $table->boolean('salary_is_active')->default(false);
            
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
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'salary_amount',
                'salary_currency',
                'salary_payment_day',
                'salary_start_date',
                'salary_is_active',
                'commission_rate',
                'commission_type',
                'commission_is_active'
            ]);
        });
    }
};
