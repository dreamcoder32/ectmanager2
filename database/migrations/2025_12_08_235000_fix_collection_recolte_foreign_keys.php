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
        // Fix collections table foreign keys
        Schema::table('collections', function (Blueprint $table) {
            // Drop existing foreign keys that have cascade delete
            $table->dropForeign(['parcel_id']);
            $table->dropForeign(['created_by']);

            // Re-add with restrict to prevent accidental data loss
            // This ensures you cannot delete a parcel or user if they have associated collections
            $table->foreign('parcel_id')
                ->references('id')
                ->on('parcels')
                ->onDelete('restrict');

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');
        });

        // Fix recoltes table foreign keys
        Schema::table('recoltes', function (Blueprint $table) {
            $table->dropForeign(['company_id']);

            // Re-add with restrict
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to cascade (dangerous, but needed for rollback)
        Schema::table('collections', function (Blueprint $table) {
            $table->dropForeign(['parcel_id']);
            $table->dropForeign(['created_by']);

            $table->foreign('parcel_id')
                ->references('id')
                ->on('parcels')
                ->onDelete('cascade');

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        Schema::table('recoltes', function (Blueprint $table) {
            $table->dropForeign(['company_id']);

            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');
        });
    }
};
