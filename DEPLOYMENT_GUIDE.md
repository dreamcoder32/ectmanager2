# Production Deployment Guide - Excel Upload Fix

## Issue
Excel uploads are failing on production with Nginx buffering warnings:
```
[warn] a client request body is buffered to a temporary file /var/cache/nginx/client_temp/
```

## Root Cause
The issue occurs when uploaded files exceed Nginx's `client_body_buffer_size`, causing Nginx to buffer the request body to temporary files. This can lead to performance issues and upload failures.

## Solution Applied

### 1. Backend Optimizations
- ✅ Increased file upload limit from 20MB to 50MB in `ParcelController.php`
- ✅ Added comprehensive logging for debugging
- ✅ Optimized batch and chunk sizes in `ParcelsImport.php` (500 instead of 100)
- ✅ Added timeout and memory limit configurations

### 2. Frontend Improvements
- ✅ Added debug logging to Excel upload function
- ✅ Increased timeout to 5 minutes for large file uploads
- ✅ Better error handling for timeout scenarios

### 3. Server Configuration Files Created
- ✅ `nginx.conf` - Recommended Nginx configuration
- ✅ `php.ini.production` - Recommended PHP configuration
- ✅ Updated `.htaccess` with PHP directives

## Deployment Steps

### Step 1: Update Application Code
Deploy the updated Laravel application with the optimized controller and import classes.

### Step 2: Apply Nginx Configuration
Apply the settings from `nginx.conf` to your production Nginx configuration:

```bash
# Edit your Nginx site configuration
sudo nano /etc/nginx/sites-available/your-site

# Add these key settings:
client_body_buffer_size 50m;
client_max_body_size 50m;
client_body_timeout 300s;
fastcgi_read_timeout 300s;

# Test and reload Nginx
sudo nginx -t
sudo systemctl reload nginx
```

### Step 3: Apply PHP Configuration
Apply the settings from `php.ini.production`:

```bash
# Edit PHP configuration
sudo nano /etc/php/8.2/fpm/php.ini

# Add/update these settings:
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300
memory_limit = 512M

# Restart PHP-FPM
sudo systemctl restart php8.2-fpm
```

### Step 4: Verify Configuration
```bash
# Check PHP settings
php -i | grep -E "(upload_max_filesize|post_max_size|max_execution_time|memory_limit)"

# Check Nginx configuration
sudo nginx -T | grep -E "(client_body_buffer_size|client_max_body_size)"
```

## Expected Results
After applying these changes:
- ✅ Excel files up to 50MB should upload successfully
- ✅ Nginx buffering warnings should be eliminated or significantly reduced
- ✅ Upload timeouts should be prevented
- ✅ Better error reporting and debugging capabilities

## Monitoring
Monitor the following logs after deployment:
- Laravel logs: `storage/logs/laravel.log`
- Nginx error logs: `/var/log/nginx/error.log`
- PHP-FPM logs: `/var/log/php8.2-fpm.log`

## Rollback Plan
If issues occur, you can quickly rollback by:
1. Reverting the Nginx configuration changes
2. Reverting the PHP configuration changes
3. Restarting services: `sudo systemctl restart nginx php8.2-fpm`