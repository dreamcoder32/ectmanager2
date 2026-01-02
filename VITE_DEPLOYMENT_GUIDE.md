# Production Deployment Guide for Vite Assets

## Problem Summary
After deploying to production and running `npm run build`, the website shows 404 errors because it's looking for old paths like `/css/app.css` instead of the new hashed Vite files.

## Root Cause
The issue was that:
1. The CSS file wasn't included in the Vite configuration
2. The `@vite` directive in the Blade template wasn't loading the CSS
3. Laravel caches needed to be cleared

## Solution Applied

### 1. Updated `vite.config.js`
Changed from:
```javascript
input: 'resources/js/app.js',
```

To:
```javascript
input: ['resources/css/app.css', 'resources/js/app.js'],
```

### 2. Updated `resources/views/app.blade.php`
Changed from:
```blade
@vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
```

To:
```blade
@vite(['resources/css/app.css', 'resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
```

### 3. Verified CSS Import in `resources/js/app.js`
Confirmed that line 1 contains:
```javascript
import '../css/app.css';
```

## Deployment Steps for Production Server

### Step 1: Upload Files to Production
Make sure these updated files are on your production server:
- `vite.config.js`
- `resources/views/app.blade.php`
- `resources/js/app.js` (should already be correct)
- `resources/css/app.css` (should already exist)

### Step 2: Build Assets on Production
```bash
cd /path/to/your/laravel/project
npm run build
```

### Step 3: Clear All Laravel Caches
```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
php artisan optimize:clear
```

### Step 4: Verify Build Files Exist
```bash
# Run the diagnostic script
chmod +x diagnose-vite.sh
./diagnose-vite.sh
```

All checks should show ✅

### Step 5: Check File Permissions
```bash
# Ensure web server can read the build files
chmod -R 755 public/build
```

### Step 6: Restart Services (if applicable)
```bash
# If using PHP-FPM
sudo systemctl restart php8.2-fpm  # Adjust version as needed

# If using Nginx
sudo systemctl restart nginx

# If using Apache
sudo systemctl restart apache2
```

## Verification

### Check the Generated HTML
View the page source in your browser and look for:
```html
<link rel="stylesheet" href="/build/assets/app-[HASH].css">
<script type="module" src="/build/assets/app-[HASH].js"></script>
```

The `[HASH]` should be a unique string like `Df95Dm7I`.

### Check Browser Console
Open browser DevTools (F12) and check:
1. **Console tab**: Should have no 404 errors
2. **Network tab**: All CSS/JS files should load with status 200

## Common Issues and Solutions

### Issue 1: Still Getting 404 Errors
**Solution**: Clear browser cache or try in incognito mode

### Issue 2: Old CSS Still Loading
**Solution**: 
```bash
php artisan view:clear
# Then hard refresh browser (Ctrl+Shift+R or Cmd+Shift+R)
```

### Issue 3: "Vite manifest not found"
**Solution**: Make sure `npm run build` completed successfully and `public/build/manifest.json` exists

### Issue 4: Assets Load but Styles Don't Apply
**Solution**: Check that `resources/css/app.css` is being imported in `resources/js/app.js`

### Issue 5: 403 Forbidden on Assets
**Solution**: 
```bash
chmod -R 755 public/build
chown -R www-data:www-data public/build  # Adjust user as needed
```

## Quick Troubleshooting Commands

```bash
# Check if manifest exists and has correct entries
cat public/build/manifest.json | grep -A 3 "resources/css/app.css"
cat public/build/manifest.json | grep -A 3 "resources/js/app.js"

# List all built assets
ls -lh public/build/assets/ | grep app-

# Check Laravel logs for errors
tail -f storage/logs/laravel.log

# Check web server error logs
# For Nginx:
tail -f /var/log/nginx/error.log
# For Apache:
tail -f /var/log/apache2/error.log
```

## Important Notes

1. **Never use `mix()` or `asset()` for Vite assets** - Always use `@vite()`
2. **Always rebuild after changes** - Run `npm run build` after any changes to Vite config
3. **Clear caches after deployment** - Laravel caches can cause old files to be served
4. **Check .gitignore** - Make sure `public/build` is in `.gitignore` (it should be)
5. **Don't commit build files** - Build assets on the production server, don't commit them

## Alternative: Build Locally and Upload

If you can't run `npm run build` on production:

1. Build locally:
   ```bash
   npm run build
   ```

2. Upload the entire `public/build` directory to production

3. Clear caches on production:
   ```bash
   php artisan optimize:clear
   ```

This is **not recommended** for production but can work in a pinch.
