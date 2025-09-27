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
        Schema::create('parcels', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_number')->unique();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('recipient_name');
            $table->string('recipient_phone', 20);
            $table->text('recipient_address');
            $table->string('wilaya_code', 10);
            $table->string('commune');
            $table->decimal('cod_amount', 10, 2)->default(0);
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->enum('status', ['pending', 'collected', 'in_transit', 'delivered', 'returned', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('collected_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->foreignId('assigned_driver_id')->nullable()->constrained('drivers')->onDelete('set null');
            $table->timestamps();
            
            $table->index(['company_id', 'status']);
            $table->index(['wilaya_code', 'commune']);
            $table->index('tracking_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parcels');
    }
};
