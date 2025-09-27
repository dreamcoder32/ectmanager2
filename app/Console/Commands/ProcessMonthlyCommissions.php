<?php

namespace App\Console\Commands;

use App\Models\CommissionPayment;
use App\Models\Collection;
use App\Models\User;
use App\Models\Expense;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcessMonthlyCommissions extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'commission:process-monthly 
                            {--month= : The month to process (1-12). Defaults to current month}
                            {--year= : The year to process. Defaults to current year}
                            {--payment-date= : The payment date. Defaults to today}
                            {--dry-run : Run without making actual changes}';

    /**
     * The console command description.
     */
    protected $description = 'Process monthly commission payments for all eligible collections';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $month = $this->option('month') ?? now()->month;
        $year = $this->option('year') ?? now()->year;
        $paymentDate = $this->option('payment-date') ?? now()->toDateString();
        $dryRun = $this->option('dry-run');

        // Validate inputs
        if ($month < 1 || $month > 12) {
            $this->error('Month must be between 1 and 12');
            return 1;
        }

        if ($year < 2020) {
            $this->error('Year must be 2020 or later');
            return 1;
        }

        $this->info("Processing commission payments for {$month}/{$year}");
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No actual changes will be made');
        }

        // Get collections eligible for commission in the specified month/year
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        $collections = Collection::with(['agent'])
            ->where('status', 'completed')
            ->whereNotNull('agent_id')
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->whereDoesntHave('commissionPayments')
            ->get();

        if ($collections->isEmpty()) {
            $this->info('No collections found eligible for commission processing');
            return 0;
        }

        $this->info("Found {$collections->count()} collections eligible for commission processing");

        $createdPayments = [];
        $skippedCollections = [];
        $errors = [];

        if (!$dryRun) {
            DB::beginTransaction();
        }

        try {
            foreach ($collections as $collection) {
                $agent = $collection->agent;
                
                if (!$agent) {
                    $skippedCollections[] = [
                        'collection_id' => $collection->id,
                        'reason' => 'No agent assigned'
                    ];
                    $this->warn("  → Skipped Collection {$collection->id}: No agent assigned");
                    continue;
                }

                if (!$agent->hasCommissionConfigured()) {
                    $skippedCollections[] = [
                        'collection_id' => $collection->id,
                        'agent' => $agent->name,
                        'reason' => 'Agent has no commission configuration'
                    ];
                    $this->warn("  → Skipped Collection {$collection->id}: Agent {$agent->name} has no commission configuration");
                    continue;
                }

                // Check if commission payment already exists
                $existingPayment = CommissionPayment::where('collection_id', $collection->id)->first();
                if ($existingPayment) {
                    $skippedCollections[] = [
                        'collection_id' => $collection->id,
                        'agent' => $agent->name,
                        'reason' => 'Commission payment already exists',
                        'existing_payment_id' => $existingPayment->id
                    ];
                    $this->warn("  → Skipped Collection {$collection->id}: Commission payment already exists (ID: {$existingPayment->id})");
                    continue;
                }

                // Calculate commission
                $commissionAmount = $agent->calculateCommission($collection->total_amount);
                
                if ($commissionAmount <= 0) {
                    $skippedCollections[] = [
                        'collection_id' => $collection->id,
                        'agent' => $agent->name,
                        'reason' => 'Commission amount is zero or negative'
                    ];
                    $this->warn("  → Skipped Collection {$collection->id}: Commission amount is zero or negative");
                    continue;
                }

                $this->line("Processing Collection {$collection->id} for agent: {$agent->name}");

                if (!$dryRun) {
                    // Create commission payment
                    $commissionPayment = CommissionPayment::create([
                        'user_id' => $agent->id,
                        'collection_id' => $collection->id,
                        'amount' => $commissionAmount,
                        'currency' => 'DZD',
                        'commission_rate' => $agent->commission_rate,
                        'base_amount' => $collection->total_amount,
                        'payment_date' => $paymentDate,
                        'status' => 'pending',
                        'payment_method' => 'bank_transfer',
                        'created_by' => 1, // System user
                        'notes' => 'Automatically generated by monthly commission processing'
                    ]);

                    // Create corresponding expense record
                    Expense::createFromCommissionPayment($commissionPayment);

                    $createdPayments[] = [
                        'collection_id' => $collection->id,
                        'agent' => $agent->name,
                        'base_amount' => $collection->total_amount,
                        'commission_rate' => $agent->commission_rate,
                        'commission_amount' => $commissionAmount,
                        'payment_id' => $commissionPayment->id
                    ];

                    $this->info("  → Created commission payment: {$commissionAmount} DZD (ID: {$commissionPayment->id})");
                } else {
                    $createdPayments[] = [
                        'collection_id' => $collection->id,
                        'agent' => $agent->name,
                        'base_amount' => $collection->total_amount,
                        'commission_rate' => $agent->commission_rate,
                        'commission_amount' => $commissionAmount,
                        'payment_id' => 'DRY_RUN'
                    ];

                    $this->info("  → Would create commission payment: {$commissionAmount} DZD");
                }
            }

            if (!$dryRun) {
                DB::commit();
            }

            // Summary
            $this->newLine();
            $this->info('=== PROCESSING SUMMARY ===');
            $this->info("Created payments: " . count($createdPayments));
            $this->info("Skipped collections: " . count($skippedCollections));
            $this->info("Errors: " . count($errors));

            if (!empty($createdPayments)) {
                $this->newLine();
                $this->info('Created Commission Payments:');
                $this->table(
                    ['Collection ID', 'Agent', 'Base Amount', 'Rate (%)', 'Commission', 'Payment ID'],
                    array_map(function ($payment) {
                        return [
                            $payment['collection_id'],
                            $payment['agent'],
                            $payment['base_amount'],
                            $payment['commission_rate'],
                            $payment['commission_amount'],
                            $payment['payment_id']
                        ];
                    }, $createdPayments)
                );
            }

            if (!empty($skippedCollections)) {
                $this->newLine();
                $this->warn('Skipped Collections:');
                $this->table(
                    ['Collection ID', 'Agent', 'Reason', 'Existing Payment ID'],
                    array_map(function ($skip) {
                        return [
                            $skip['collection_id'],
                            isset($skip['agent']) ? $skip['agent'] : 'N/A',
                            $skip['reason'],
                            isset($skip['existing_payment_id']) ? $skip['existing_payment_id'] : 'N/A'
                        ];
                    }, $skippedCollections)
                );
            }

            // Log the operation
            Log::info('Monthly commission processing completed', [
                'month' => $month,
                'year' => $year,
                'payment_date' => $paymentDate,
                'dry_run' => $dryRun,
                'created_count' => count($createdPayments),
                'skipped_count' => count($skippedCollections),
                'error_count' => count($errors)
            ]);

            return 0;

        } catch (\Exception $e) {
            if (!$dryRun) {
                DB::rollBack();
            }

            $this->error('Failed to process commission payments: ' . $e->getMessage());
            Log::error('Monthly commission processing failed', [
                'month' => $month,
                'year' => $year,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return 1;
        }
    }
}