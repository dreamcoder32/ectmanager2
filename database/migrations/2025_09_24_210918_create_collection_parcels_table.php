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
        Schema::create('collection_parcels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->constrained('collections')->onDelete('cascade');
            $table->foreignId('parcel_id')->constrained('parcels')->onDelete('cascade');
            $table->enum('status', ['assigned', 'collected', 'delivered', 'returned', 'failed'])->default('assigned');
            $table->text('delivery_notes')->nullable();
            $table->timestamp('collected_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->decimal('collected_amount', 10, 2)->nullable();
            $table->timestamps();
            
            $table->unique(['collection_id', 'parcel_id']);
            $table->index(['collection_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collection_parcels');
    }
};
