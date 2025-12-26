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
        Schema::create("parcel_price_changes", function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId("parcel_id")
                ->constrained("parcels")
                ->onDelete("cascade");
            $table->decimal("old_price", 10, 2);
            $table->decimal("new_price", 10, 2);
            $table
                ->foreignId("changed_by")
                ->constrained("users")
                ->onDelete("cascade");
            $table->text("reason")->nullable();
            $table->timestamps();

            $table->index("parcel_id");
            $table->index("changed_by");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("parcel_price_changes");
    }
};
