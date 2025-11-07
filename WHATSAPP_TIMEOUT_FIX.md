# WhatsApp API Timeout Fix Documentation

## Problem Summary

The application was experiencing timeout errors when trying to connect to the WhatsApp API service (wasenderapi.com):

```
cURL error 28: Connection timed out after 10002 milliseconds
```

### Error Logs
```
[2025-11-07 19:56:51] local.ERROR: Error fetching WhatsApp session.
[2025-11-07 19:56:51] local.ERROR: WhatsApp session connection error
[2025-11-07 19:57:01] local.ERROR: WhatsApp session QR code error
```

## Root Causes

1. **Missing Timeout Configuration**: The `getWhatsAppSession()` method was using Laravel's default HTTP timeout (10 seconds), which was insufficient for the external API
2. **No Retry Logic**: Single API calls with no retry mechanism meant temporary network issues caused immediate failures
3. **Inconsistent Timeout Settings**: Some methods had `timeout(60)` while others had no timeout specified
4. **No Connection Timeout**: Only request timeout was set, but connection establishment timeout was missing
5. **PHP Execution Time Limit**: Default 30-second limit was being exceeded when retries were attempted
6. **Guzzle Default Timeouts**: The underlying Guzzle client has its own default timeouts that need explicit configuration

## Solutions Implemented

### 1. Added Timeout Configuration to All HTTP Requests

Updated the following methods in `app/Services/WhatsAppService.php`:

#### `getWhatsAppSession()` - Lines 372-495
- Added `withOptions()` with explicit Guzzle configuration:
  - `timeout: 20` - 20 seconds for the entire request to complete
  - `connect_timeout: 15` - 15 seconds to establish connection
- Added `set_time_limit(60)` - Extend PHP execution time to 60 seconds
- Reduced retries to 2 attempts with 0.5s delay to fit within execution time

```php
// Allow more execution time for API calls with retries
set_time_limit(60);

$response = Http::withOptions([
    'timeout' => 20,
    'connect_timeout' => 15,
])
    ->withHeaders([...])
    ->get($this->baseUrl . '/whatsapp-sessions');
```

#### `connectSession()` - Line 509
- Added `set_time_limit(60)` for extended execution time
- Added `withOptions(['timeout' => 20, 'connect_timeout' => 15])`

#### `getSessionQrCode()` - Line 591
- Added `set_time_limit(60)` for extended execution time
- Added `withOptions(['timeout' => 20, 'connect_timeout' => 15])`

#### `fetchSessionQrCode()` - Line 669
- Added `set_time_limit(60)` for extended execution time

#### `testConnection()` - Line 286
- Added `withOptions(['timeout' => 20, 'connect_timeout' => 15])`

#### `getSessionStatus()` - Line 328
- Added `withOptions(['timeout' => 20, 'connect_timeout' => 15])`

#### `checkPhoneOnWhatsApp()` - Line 839
- Added `withOptions(['timeout' => 20, 'connect_timeout' => 15])`

### 2. Implemented Retry Logic with Exponential Backoff

Enhanced `getWhatsAppSession()` with intelligent retry mechanism:

- **Maximum Retries**: 2 attempts (reduced to fit within PHP execution time)
- **Linear Backoff**: 0.5 seconds between retries (optimized for speed)
- **Extended PHP Execution Time**: 60 seconds to accommodate retries
- **Smart Error Handling**: 
  - Retries on 5xx (server errors) and timeout errors
  - Skips retry on 4xx (client errors) as they won't succeed
- **Detailed Logging**: Each attempt is logged with context
- **Guzzle Options**: Direct configuration of timeout and connect_timeout

```php
private function getWhatsAppSession(Company $company, int $maxRetries = 2): ?array
{
    // Allow more execution time for API calls with retries
    set_time_limit(60);
    
    $attempt = 0;
    while ($attempt < $maxRetries) {
        try {
            $response = Http::withOptions([
                'timeout' => 20,
                'connect_timeout' => 15,
            ])
                ->withHeaders([...])
                ->get($this->baseUrl . '/whatsapp-sessions');
            
            if ($response->successful()) {
                return $session;
            }
            
            // Skip retry on client errors (4xx)
            if ($response->status() >= 400 && $response->status() < 500) {
                return null;
            }
        } catch (Exception $e) {
            // Log and continue to retry
        }
        
        $attempt++;
        if ($attempt < $maxRetries) {
            $waitTime = 0.5; // 500ms linear backoff
            usleep($waitTime * 1000000);
        }
    }
    return null;
}
```

### 3. Enhanced Error Logging

Added comprehensive logging at multiple levels:

- **Info**: Successful retry attempts
- **Warning**: Failed attempts with retry remaining
- **Error**: All retries exhausted or client errors

Example log output:
```php
Log::info('Retrying WhatsApp session fetch in 2 seconds', [
    'company_id' => $company->id,
    'attempt' => 2,
    'max_retries' => 3,
]);
```

## Configuration

### Timeout Settings (in code)
The timeout settings are configured directly in the service methods:
- **Request Timeout**: 20 seconds
- **Connection Timeout**: 15 seconds
- **PHP Execution Time**: 60 seconds (for methods with retries)

### Retry Settings
- **Max Retries**: 2 attempts
- **Backoff Delay**: 0.5 seconds (linear)

### Adjusting Timeouts
To modify timeout values, edit `app/Services/WhatsAppService.php`:

```php
$response = Http::withOptions([
    'timeout' => 20,        // Increase if API is consistently slow
    'connect_timeout' => 15, // Increase if connection takes longer
])
```

### PHP Configuration
Ensure your PHP configuration allows sufficient execution time:

```ini
; php.ini or .user.ini
max_execution_time = 60
```

## Testing Recommendations

### 1. Test Normal Operation
```bash
# Test WhatsApp session connection
php artisan tinker
>>> $company = App\Models\Company::find(1);
>>> $service = new App\Services\WhatsAppService();
>>> $service->getSessionStatus($company);
```

### 2. Test Timeout Scenarios
- Temporarily block the API domain to simulate network issues
- Verify retry logic triggers and logs properly
- Confirm exponential backoff timing

### 3. Monitor Logs
```bash
tail -f storage/logs/laravel.log | grep WhatsApp
```

Look for:
- Successful connections
- Retry attempts with timestamps
- Timeout errors (should be reduced significantly)

## Expected Improvements

### Before Fix
- ❌ Timeout after 10 seconds
- ❌ Single failed attempt = complete failure
- ❌ No visibility into retry attempts
- ❌ Poor reliability with network fluctuations
- ❌ PHP execution time exceeded with retries

### After Fix
- ✅ 20 second request timeout (2x longer)
- ✅ 15 second connection timeout
- ✅ Up to 2 retry attempts with 0.5s linear backoff
- ✅ Extended PHP execution time (60s) for retry operations
- ✅ Connection timeout separate from request timeout
- ✅ Direct Guzzle configuration via withOptions()
- ✅ Comprehensive logging for debugging
- ✅ Smart error handling (don't retry 4xx errors)
- ✅ Better resilience to temporary network issues
- ✅ Optimized timing to prevent execution time errors

## Network Issues Addressed

1. **Slow API Response**: 20-second timeout allows for slower responses
2. **Connection Establishment Issues**: 15-second connection timeout
3. **Temporary Network Hiccups**: Retry logic with linear backoff
4. **Server-side Issues**: Retries give server time to recover
5. **PHP Execution Limits**: Extended execution time prevents premature termination
6. **Guzzle Default Overrides**: Explicit timeout configuration ensures proper behavior

## Monitoring

Watch for these patterns in logs:

### Success Pattern
```
[INFO] WhatsApp message sent successfully
```

### Retry Pattern (Normal)
```
[WARNING] Error fetching WhatsApp session (attempt 1)
[INFO] Retrying WhatsApp session fetch in 0.5 seconds
[INFO] WhatsApp session retrieved after retry
```

### Failure Pattern (Needs Investigation)
```
[WARNING] Error fetching WhatsApp session (attempt 1)
[INFO] Retrying WhatsApp session fetch in 0.5 seconds
[WARNING] Error fetching WhatsApp session (attempt 2)
[ERROR] All retry attempts exhausted for WhatsApp session fetch
```

## Troubleshooting

### If timeouts still occur:

1. **Check Network Connectivity**
   ```bash
   curl -v https://www.wasenderapi.com/api/whatsapp-sessions
   ```

2. **Verify API Key Configuration**
   ```bash
   php artisan config:clear
   echo $WSNAPI_KEY
   ```

3. **Increase Timeout (if needed)**
   - Edit `app/Services/WhatsAppService.php`
   - Change `'timeout' => 20` to `'timeout' => 30` or higher in `withOptions()`
   - Also consider increasing `'connect_timeout' => 15`

4. **Check API Service Status**
   - Contact wasenderapi.com support
   - Check their status page for outages

5. **Review Server Resources**
   - Check PHP max_execution_time setting (should be at least 60)
   - Verify adequate memory and CPU
   - Check web server timeout settings (nginx, Apache)

6. **PHP Execution Time**
   ```bash
   php -i | grep max_execution_time
   # Should be at least 60 seconds
   ```

## Additional Notes

- The `sendMessage()` method already had `timeout(60)` which was kept for sending messages
- All session-related HTTP requests now use `withOptions(['timeout' => 20, 'connect_timeout' => 15])`
- The retry logic is only implemented in `getWhatsAppSession()` as it's the critical path
- Connection timeout of 15 seconds balances between reliability and quick failure detection
- PHP execution time is extended to 60 seconds for methods that use retries
- Using `withOptions()` ensures Guzzle respects our timeout configuration
- Linear backoff (0.5s) is faster than exponential while still preventing API overwhelming
- Total execution time: ~41 seconds max (20s + 0.5s + 20s for 2 attempts)
- The configuration is production-ready and optimized for the 30-second PHP default limit

## Related Files

- `app/Services/WhatsAppService.php` - Main service with fixes
- `app/Http/Controllers/WhatsAppController.php` - Controller using the service
- `config/whatsapp.php` - Configuration file
- `storage/logs/laravel.log` - Check for timeout errors

## Maintenance

- Monitor timeout errors in production logs
- Adjust timeout values based on actual API performance
- If timeouts persist, consider increasing to `'timeout' => 30`
- Monitor PHP execution time errors - may need to increase `set_time_limit()`
- Consider implementing circuit breaker pattern if issues persist
- Keep track of wasenderapi.com API updates and SLA changes
- Review retry count and backoff timing based on production metrics

## Performance Metrics to Track

1. **Average API Response Time**: Should be < 10 seconds
2. **Retry Success Rate**: % of calls that succeed on retry
3. **Timeout Error Rate**: Should decrease significantly
4. **PHP Execution Time Errors**: Should be eliminated with current configuration