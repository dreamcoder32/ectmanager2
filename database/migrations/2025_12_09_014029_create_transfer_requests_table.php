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
        Schema::create('transfer_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supervisor_id')->constrained('users');
            $table->foreignId('admin_id')->constrained('users');
            $table->enum('status', ['pending', 'success', 'rejected'])->default('pending');
            $table->string('verification_code', 6);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::table('recoltes', function (Blueprint $table) {
            $table->foreignId('transfer_request_id')->nullable()->constrained('transfer_requests')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recoltes', function (Blueprint $table) {
            $table->dropForeign(['transfer_request_id']);
            $table->dropColumn('transfer_request_id');
        });

        Schema::dropIfExists('transfer_requests');
    }
};
