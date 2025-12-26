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
        Schema::table('expenses', function (Blueprint $table) {
            // Drop the existing category index first
            $table->dropIndex(['category', 'expense_date']);
            
            // Drop the existing category enum column
            $table->dropColumn('category');
            
            // Add the new category_id foreign key
            $table->foreignId('category_id')->nullable()->constrained('expense_categories')->onDelete('set null')->after('currency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            // Drop the foreign key and column
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
            
            // Restore the original category enum column
            $table->enum('category', [
                'salary', 'commission', 'fuel', 'maintenance', 
                'supplies', 'rent', 'utilities', 'marketing', 'other'
            ])->after('currency');
            
            // Restore the index
            $table->index(['category', 'expense_date']);
        });
    }
};
