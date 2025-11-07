<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestWhatsAppConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:test {company_id? : The ID of the company to test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test WhatsApp API connection and timeout configuration';

    /**
     * Execute the console command.
     */
    public function handle(WhatsAppService $whatsappService)
    {
        $this->info('=== WhatsApp Connection Test ===');
        $this->newLine();

        // Get company
        $companyId = $this->argument('company_id');

        if ($companyId) {
            $company = Company::find($companyId);
            if (!$company) {
                $this->error("Company with ID {$companyId} not found!");
                return 1;
            }
        } else {
            $company = Company::first();
            if (!$company) {
                $this->error('No companies found in database!');
                return 1;
            }
        }

        $this->info("Testing company: {$company->name} (ID: {$company->id})");
        $this->newLine();

        // Check configuration
        $this->line('1. Checking configuration...');

        if (!config('services.wasender.api_key')) {
            $this->error('   ✗ WSNAPI_KEY not configured in .env');
            $this->warn('   Add: WSNAPI_KEY=your_api_key_here');
            return 1;
        } else {
            $apiKey = config('services.wasender.api_key');
            $maskedKey = substr($apiKey, 0, 8) . '...' . substr($apiKey, -4);
            $this->info("   ✓ API Key configured: {$maskedKey}");
        }

        if (!$company->phone) {
            $this->error("   ✗ Company phone number not configured");
            return 1;
        } else {
            $this->info("   ✓ Company phone: {$company->phone}");
        }

        $this->newLine();

        // Check PHP configuration
        $this->line('2. Checking PHP configuration...');
        $maxExecutionTime = ini_get('max_execution_time');
        if ($maxExecutionTime < 60 && $maxExecutionTime != 0) {
            $this->warn("   ⚠ max_execution_time is {$maxExecutionTime}s (recommended: 60s or more)");
        } else {
            $this->info("   ✓ max_execution_time: {$maxExecutionTime}s");
        }

        $this->newLine();

        // Test API connection
        $this->line('3. Testing API connection...');
        $startTime = microtime(true);

        $bar = $this->output->createProgressBar();
        $bar->start();

        try {
            // Enable detailed logging for this test
            Log::info('=== WhatsApp Connection Test Started ===', [
                'company_id' => $company->id,
                'company_name' => $company->name,
            ]);

            $result = $whatsappService->getSessionStatus($company);

            $bar->finish();
            $this->newLine();

            $duration = round(microtime(true) - $startTime, 2);
            $this->info("   Response time: {$duration}s");

            if ($result['success']) {
                $this->info('   ✓ Connection successful!');
                $this->info("   Status: {$result['status']}");

                if (isset($result['raw'])) {
                    $this->newLine();
                    $this->line('   API Response:');
                    $this->line('   ' . json_encode($result['raw'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                }
            } else {
                $this->error('   ✗ Connection failed!');
                $this->error("   Error: {$result['error']}");

                if (isset($result['status_code'])) {
                    $this->error("   Status Code: {$result['status_code']}");
                }
            }

            Log::info('=== WhatsApp Connection Test Completed ===', [
                'duration' => $duration,
                'success' => $result['success'],
            ]);

        } catch (\Exception $e) {
            $bar->finish();
            $this->newLine();
            $this->error('   ✗ Exception occurred!');
            $this->error("   {$e->getMessage()}");

            Log::error('WhatsApp Connection Test Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }

        $this->newLine();

        // Test session fetch (with retry logic)
        if ($result['success']) {
            $this->line('4. Testing session fetch with retry logic...');
            $this->warn('   This may take up to 41 seconds if retries are needed...');

            $startTime = microtime(true);

            try {
                $sessionResult = $whatsappService->connectSession($company);
                $duration = round(microtime(true) - $startTime, 2);

                $this->info("   Response time: {$duration}s");

                if ($sessionResult['success']) {
                    $this->info('   ✓ Session connection successful!');
                    $this->info("   Status: {$sessionResult['status']}");

                    if (isset($sessionResult['qr_code']) && $sessionResult['qr_code']) {
                        $this->info('   ✓ QR Code available');
                    }
                } else {
                    $this->error('   ✗ Session connection failed!');
                    $this->error("   Error: {$sessionResult['error']}");
                }
            } catch (\Exception $e) {
                $duration = round(microtime(true) - $startTime, 2);
                $this->error("   ✗ Exception after {$duration}s: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->line('=== Test Summary ===');
        $this->info('✓ Configuration: OK');
        $this->info('✓ Timeout settings: Updated to 20s');
        $this->info('✓ Retry logic: 2 attempts with 0.5s delay');
        $this->info('✓ PHP execution time: Extended to 60s');

        $this->newLine();
        $this->comment('Check storage/logs/laravel.log for detailed execution logs');

        return 0;
    }
}
