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
            // Drop existing indexes first
            $table->dropIndex(['driver_id', 'expense_date']);
            $table->dropIndex(['expense_type', 'expense_date']);
            
            // Drop foreign key constraints
            $table->dropForeign(['driver_id']);
            $table->dropForeign(['collection_id']);
            $table->dropForeign(['approved_by']);
            
            // Drop existing columns that don't match PRD2
            $table->dropColumn(['driver_id', 'collection_id', 'expense_type', 'receipt_path', 'is_approved', 'approved_by', 'approved_at']);
            
            // Add new columns according to PRD2
            $table->string('title')->after('id');
            $table->string('currency', 3)->default('DZD')->after('amount');
            $table->enum('category', [
                'salary', 'commission', 'fuel', 'maintenance', 
                'supplies', 'rent', 'utilities', 'marketing', 'other'
            ])->after('currency');
            
            // Payment tracking
            $table->enum('status', ['pending', 'approved', 'paid', 'rejected'])->default('pending')->after('expense_date');
            $table->enum('payment_method', ['cash', 'bank_transfer', 'check', 'card'])->default('cash')->after('status');
            $table->date('payment_date')->nullable()->after('payment_method');
            
            // References for salary/commission payments
            $table->enum('reference_type', ['salary_payment', 'commission_payment', 'manual'])->default('manual')->after('payment_date');
            $table->unsignedBigInteger('reference_id')->nullable()->after('reference_type');
            
            // User tracking
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null')->after('reference_id');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null')->after('created_by');
            $table->foreignId('paid_by')->nullable()->constrained('users')->onDelete('set null')->after('approved_by');
            
            // Timestamps for tracking
            $table->timestamp('approved_at')->nullable()->after('paid_by');
            $table->timestamp('paid_at')->nullable()->after('approved_at');
            
            // Add indexes
            $table->index(['category', 'expense_date']);
            $table->index('status');
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            // Drop new columns
            $table->dropIndex(['category', 'expense_date']);
            $table->dropIndex(['status']);
            $table->dropIndex(['reference_type', 'reference_id']);
            
            $table->dropForeign(['created_by']);
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['paid_by']);
            
            $table->dropColumn([
                'title', 'currency', 'category', 'status', 'payment_method', 
                'payment_date', 'reference_type', 'reference_id', 'created_by', 
                'approved_by', 'paid_by', 'approved_at', 'paid_at'
            ]);
            
            // Restore original columns
            $table->foreignId('driver_id')->constrained('drivers')->onDelete('cascade');
            $table->foreignId('collection_id')->nullable()->constrained('collections')->onDelete('set null');
            $table->enum('expense_type', ['fuel', 'maintenance', 'toll', 'parking', 'other']);
            $table->string('receipt_path')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            
            // Restore indexes
            $table->index(['driver_id', 'expense_date']);
            $table->index(['expense_type', 'expense_date']);
        });
    }
};
