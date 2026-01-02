#!/bin/bash

echo "=== Vite Build Diagnostics ==="
echo ""

echo "1. Checking if build directory exists:"
ls -la public/build/ 2>/dev/null || echo "❌ Build directory not found!"
echo ""

echo "2. Checking manifest.json:"
if [ -f "public/build/manifest.json" ]; then
    echo "✅ Manifest exists"
    echo "Checking for app.css entry:"
    grep -q "resources/css/app.css" public/build/manifest.json && echo "✅ CSS entry found" || echo "❌ CSS entry missing"
    echo "Checking for app.js entry:"
    grep -q "resources/js/app.js" public/build/manifest.json && echo "✅ JS entry found" || echo "❌ JS entry missing"
else
    echo "❌ Manifest not found!"
fi
echo ""

echo "3. Checking actual CSS file:"
CSS_FILE=$(grep -A 2 '"resources/css/app.css"' public/build/manifest.json | grep '"file"' | cut -d'"' -f4)
if [ -n "$CSS_FILE" ]; then
    echo "CSS file should be: public/build/$CSS_FILE"
    [ -f "public/build/$CSS_FILE" ] && echo "✅ CSS file exists" || echo "❌ CSS file missing!"
else
    echo "❌ Could not determine CSS filename"
fi
echo ""

echo "4. Checking actual JS file:"
JS_FILE=$(grep -A 4 '"resources/js/app.js"' public/build/manifest.json | grep '"file"' | cut -d'"' -f4)
if [ -n "$JS_FILE" ]; then
    echo "JS file should be: public/build/$JS_FILE"
    [ -f "public/build/$JS_FILE" ] && echo "✅ JS file exists" || echo "❌ JS file missing!"
else
    echo "❌ Could not determine JS filename"
fi
echo ""

echo "5. Laravel cache status:"
echo "Config cache:"
[ -f "bootstrap/cache/config.php" ] && echo "⚠️  Config is cached (may need clearing)" || echo "✅ No config cache"
echo "Route cache:"
[ -f "bootstrap/cache/routes-v7.php" ] && echo "⚠️  Routes are cached (may need clearing)" || echo "✅ No route cache"
echo "View cache:"
[ -d "storage/framework/views" ] && echo "⚠️  Views may be cached" || echo "✅ No view cache dir"
echo ""

echo "6. Recommended commands to run on production:"
echo "   php artisan config:clear"
echo "   php artisan route:clear"
echo "   php artisan view:clear"
echo "   php artisan cache:clear"
echo ""

echo "7. Check .env for ASSET_URL:"
if [ -f ".env" ]; then
    ASSET_URL=$(grep "^ASSET_URL=" .env)
    if [ -n "$ASSET_URL" ]; then
        echo "Found: $ASSET_URL"
    else
        echo "✅ ASSET_URL not set (using default)"
    fi
else
    echo "❌ .env file not found"
fi
