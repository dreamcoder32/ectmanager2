<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, backup existing data (drop existing backups if they exist)
        DB::statement('DROP TABLE IF EXISTS collections_backup');
        DB::statement('DROP TABLE IF EXISTS collection_parcels_backup');
        
        // Check if collections table exists before backing it up
        if (Schema::hasTable('collections')) {
            DB::statement('CREATE TABLE collections_backup AS SELECT * FROM collections');
        }
        
        // Check if collection_parcels table exists before backing it up
        if (Schema::hasTable('collection_parcels')) {
            DB::statement('CREATE TABLE collection_parcels_backup AS SELECT * FROM collection_parcels');
        }
        
        // Drop foreign key constraints first
        if (Schema::hasTable('collection_parcels')) {
            Schema::table('collection_parcels', function (Blueprint $table) {
                $table->dropForeign(['collection_id']);
                $table->dropForeign(['parcel_id']);
            });
        }
        
        // Check if commission_payments table exists and has collection_id foreign key
        if (Schema::hasTable('commission_payments') && Schema::hasColumn('commission_payments', 'collection_id')) {
            Schema::table('commission_payments', function (Blueprint $table) {
                $table->dropForeign(['collection_id']);
            });
        }
        
        // Note: expenses table no longer has collection_id after financial management update
        
        // Drop the pivot table
        Schema::dropIfExists('collection_parcels');
        
        // Drop the old collections table
        Schema::dropIfExists('collections');
        
        // Create the new simplified collections table
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->datetime('collected_at'); // When the parcel was collected/delivered
            $table->foreignId('parcel_id')->constrained('parcels')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade'); // User who recorded the collection
            $table->text('note')->nullable(); // Collection notes
            $table->decimal('amount', 10, 2); // Amount collected
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->onDelete('set null'); // Only for home delivery
            $table->decimal('margin', 10, 2)->nullable(); // Company margin
            $table->decimal('driver_commission', 10, 2)->nullable(); // Driver commission for home delivery
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['parcel_id']);
            $table->index(['created_by']);
            $table->index(['driver_id']);
            $table->index(['collected_at']);
        });
        
        // Update foreign key references in other tables
        Schema::table('commission_payments', function (Blueprint $table) {
            $table->foreignId('collection_id')->nullable()->change();
            $table->foreign('collection_id')->references('id')->on('collections')->onDelete('set null');
        });
        
        // Note: expenses table no longer has collection_id after financial management update
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign key constraints if they exist
        if (Schema::hasTable('commission_payments') && Schema::hasColumn('commission_payments', 'collection_id')) {
            Schema::table('commission_payments', function (Blueprint $table) {
                $table->dropForeign(['collection_id']);
            });
        }
        
        // Drop the new collections table
        Schema::dropIfExists('collections');
        
        // Restore the old structure from backup if backups exist
        if (DB::select("SHOW TABLES LIKE 'collections_backup'")) {
            DB::statement('CREATE TABLE collections AS SELECT * FROM collections_backup');
        }
        
        if (DB::select("SHOW TABLES LIKE 'collection_parcels_backup'")) {
            DB::statement('CREATE TABLE collection_parcels AS SELECT * FROM collection_parcels_backup');
            
            // Recreate foreign keys for the old structure
            Schema::table('collection_parcels', function (Blueprint $table) {
                $table->foreign('collection_id')->references('id')->on('collections')->onDelete('cascade');
                $table->foreign('parcel_id')->references('id')->on('parcels')->onDelete('cascade');
            });
        }
        
        // Recreate commission_payments foreign key if collections table was restored
        if (Schema::hasTable('collections') && Schema::hasTable('commission_payments') && Schema::hasColumn('commission_payments', 'collection_id')) {
            Schema::table('commission_payments', function (Blueprint $table) {
                $table->foreign('collection_id')->references('id')->on('collections')->onDelete('set null');
            });
        }
        
        // Clean up backup tables
        DB::statement('DROP TABLE IF EXISTS collections_backup');
        DB::statement('DROP TABLE IF EXISTS collection_parcels_backup');
    }
};