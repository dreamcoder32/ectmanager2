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
            // Personal Information
            $table->string('first_name')->nullable()->after('first_name');
            $table->string('last_name')->nullable()->after('first_name');
            $table->date('date_of_birth')->nullable()->after('last_name');
            $table->string('identity_card_number')->nullable()->unique()->after('date_of_birth');
            $table->string('national_identification_number')->nullable()->unique()->after('identity_card_number');
            
            // Employment Information
            $table->date('started_working_at')->nullable()->after('national_identification_number');
            $table->integer('payment_day_of_month')->nullable()->default(1)->after('started_working_at');
            $table->decimal('monthly_salary', 10, 2)->nullable()->default(0)->after('payment_day_of_month');
            
            // Manager relationship (supervisor can manage agents)
            $table->unsignedBigInteger('manager_id')->nullable()->after('monthly_salary');
            $table->foreign('manager_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['manager_id']);
            $table->dropColumn([
                'first_name',
                'last_name', 
                'date_of_birth',
                'identity_card_number',
                'national_identification_number',
                'started_working_at',
                'payment_day_of_month',
                'monthly_salary',
                'manager_id'
            ]);
        });
    }
};
