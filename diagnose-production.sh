#!/bin/bash

# Production Server Vite Diagnostic Script
# Run this on your production server to diagnose asset loading issues

echo "================================================"
echo "  PRODUCTION VITE DIAGNOSTICS"
echo "================================================"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print status
print_status() {
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✅ $2${NC}"
    else
        echo -e "${RED}❌ $2${NC}"
    fi
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

# 1. Check Laravel installation
echo "1. LARAVEL INSTALLATION"
echo "------------------------"
if [ -f "artisan" ]; then
    print_status 0 "Laravel detected"
    php artisan --version
else
    print_status 1 "Laravel not found - are you in the project root?"
    exit 1
fi
echo ""

# 2. Check Node.js and npm
echo "2. NODE.JS ENVIRONMENT"
echo "----------------------"
if command -v node &> /dev/null; then
    print_status 0 "Node.js installed: $(node --version)"
else
    print_status 1 "Node.js not found"
fi

if command -v npm &> /dev/null; then
    print_status 0 "npm installed: $(npm --version)"
else
    print_status 1 "npm not found"
fi
echo ""

# 3. Check Vite configuration
echo "3. VITE CONFIGURATION"
echo "---------------------"
if [ -f "vite.config.js" ]; then
    print_status 0 "vite.config.js exists"
    
    # Check if CSS is in input
    if grep -q "resources/css/app.css" vite.config.js; then
        print_status 0 "CSS file configured in Vite"
    else
        print_status 1 "CSS file NOT in Vite config"
        echo "   Add 'resources/css/app.css' to input array"
    fi
    
    # Check if JS is in input
    if grep -q "resources/js/app.js" vite.config.js; then
        print_status 0 "JS file configured in Vite"
    else
        print_status 1 "JS file NOT in Vite config"
    fi
else
    print_status 1 "vite.config.js not found"
fi
echo ""

# 4. Check source files
echo "4. SOURCE FILES"
echo "---------------"
[ -f "resources/css/app.css" ] && print_status 0 "resources/css/app.css exists" || print_status 1 "resources/css/app.css missing"
[ -f "resources/js/app.js" ] && print_status 0 "resources/js/app.js exists" || print_status 1 "resources/js/app.js missing"
[ -f "resources/views/app.blade.php" ] && print_status 0 "resources/views/app.blade.php exists" || print_status 1 "resources/views/app.blade.php missing"
echo ""

# 5. Check Blade template
echo "5. BLADE TEMPLATE"
echo "-----------------"
if [ -f "resources/views/app.blade.php" ]; then
    if grep -q "@vite" resources/views/app.blade.php; then
        print_status 0 "@vite directive found"
        
        # Show the actual @vite line
        echo "   Current @vite directive:"
        grep "@vite" resources/views/app.blade.php | sed 's/^/   /'
        
        # Check if it includes CSS
        if grep "@vite" resources/views/app.blade.php | grep -q "resources/css/app.css"; then
            print_status 0 "CSS included in @vite directive"
        else
            print_status 1 "CSS NOT in @vite directive"
            echo "   Should be: @vite(['resources/css/app.css', 'resources/js/app.js', ...])"
        fi
    else
        print_status 1 "@vite directive not found"
        echo "   Looking for old asset() or mix() calls:"
        if grep -E "(asset\(|mix\()" resources/views/app.blade.php; then
            print_warning "Found old asset loading methods - replace with @vite"
        fi
    fi
fi
echo ""

# 6. Check build directory
echo "6. BUILD DIRECTORY"
echo "------------------"
if [ -d "public/build" ]; then
    print_status 0 "public/build directory exists"
    
    if [ -f "public/build/manifest.json" ]; then
        print_status 0 "manifest.json exists"
        
        # Check manifest entries
        if grep -q '"resources/css/app.css"' public/build/manifest.json; then
            print_status 0 "CSS entry in manifest"
            CSS_FILE=$(grep -A 2 '"resources/css/app.css"' public/build/manifest.json | grep '"file"' | cut -d'"' -f4)
            echo "   CSS file: $CSS_FILE"
            [ -f "public/build/$CSS_FILE" ] && print_status 0 "CSS file exists on disk" || print_status 1 "CSS file missing!"
        else
            print_status 1 "CSS entry NOT in manifest"
        fi
        
        if grep -q '"resources/js/app.js"' public/build/manifest.json; then
            print_status 0 "JS entry in manifest"
            JS_FILE=$(grep -A 4 '"resources/js/app.js"' public/build/manifest.json | grep '"file"' | cut -d'"' -f4 | head -1)
            echo "   JS file: $JS_FILE"
            [ -f "public/build/$JS_FILE" ] && print_status 0 "JS file exists on disk" || print_status 1 "JS file missing!"
        else
            print_status 1 "JS entry NOT in manifest"
        fi
    else
        print_status 1 "manifest.json missing - run 'npm run build'"
    fi
    
    # Check file permissions
    echo ""
    echo "   Build directory permissions:"
    ls -ld public/build | awk '{print "   Owner: "$3", Group: "$4", Permissions: "$1}'
    
    # Count asset files
    ASSET_COUNT=$(find public/build/assets -type f 2>/dev/null | wc -l)
    echo "   Total asset files: $ASSET_COUNT"
else
    print_status 1 "public/build directory missing - run 'npm run build'"
fi
echo ""

# 7. Check Laravel caches
echo "7. LARAVEL CACHES"
echo "-----------------"
[ -f "bootstrap/cache/config.php" ] && print_warning "Config cached (run: php artisan config:clear)" || print_status 0 "Config not cached"
[ -f "bootstrap/cache/routes-v7.php" ] && print_warning "Routes cached (run: php artisan route:clear)" || print_status 0 "Routes not cached"

VIEW_CACHE_COUNT=$(find storage/framework/views -name "*.php" 2>/dev/null | wc -l)
if [ $VIEW_CACHE_COUNT -gt 0 ]; then
    print_warning "Views cached ($VIEW_CACHE_COUNT files) (run: php artisan view:clear)"
else
    print_status 0 "Views not cached"
fi
echo ""

# 8. Check .env configuration
echo "8. ENVIRONMENT CONFIGURATION"
echo "----------------------------"
if [ -f ".env" ]; then
    print_status 0 ".env file exists"
    
    APP_ENV=$(grep "^APP_ENV=" .env | cut -d'=' -f2)
    echo "   APP_ENV: ${APP_ENV:-not set}"
    
    APP_DEBUG=$(grep "^APP_DEBUG=" .env | cut -d'=' -f2)
    echo "   APP_DEBUG: ${APP_DEBUG:-not set}"
    
    ASSET_URL=$(grep "^ASSET_URL=" .env | cut -d'=' -f2)
    if [ -n "$ASSET_URL" ]; then
        echo "   ASSET_URL: $ASSET_URL"
    else
        print_status 0 "ASSET_URL not set (using default)"
    fi
else
    print_status 1 ".env file missing"
fi
echo ""

# 9. Check web server user
echo "9. WEB SERVER PERMISSIONS"
echo "-------------------------"
if command -v ps &> /dev/null; then
    # Try to detect web server process
    if ps aux | grep -E "(nginx|httpd|apache)" | grep -v grep > /dev/null; then
        WEB_USER=$(ps aux | grep -E "(nginx|httpd|apache)" | grep -v grep | head -1 | awk '{print $1}')
        echo "   Web server running as: $WEB_USER"
        
        # Check if web server can read build files
        if [ -d "public/build" ]; then
            BUILD_OWNER=$(ls -ld public/build | awk '{print $3}')
            echo "   Build directory owner: $BUILD_OWNER"
            
            if [ "$WEB_USER" != "$BUILD_OWNER" ]; then
                print_warning "Web server user differs from build owner"
                echo "   Consider running: sudo chown -R $WEB_USER:$WEB_USER public/build"
            fi
        fi
    else
        echo "   Web server process not detected"
    fi
fi
echo ""

# 10. Recommendations
echo "================================================"
echo "  RECOMMENDATIONS"
echo "================================================"
echo ""

ISSUES_FOUND=0

# Check if build is needed
if [ ! -f "public/build/manifest.json" ]; then
    echo "🔧 Run: npm run build"
    ISSUES_FOUND=1
fi

# Check if caches need clearing
if [ -f "bootstrap/cache/config.php" ] || [ -f "bootstrap/cache/routes-v7.php" ] || [ $VIEW_CACHE_COUNT -gt 0 ]; then
    echo "🔧 Run: php artisan optimize:clear"
    ISSUES_FOUND=1
fi

# Check if Vite config needs updating
if [ -f "vite.config.js" ] && ! grep -q "resources/css/app.css" vite.config.js; then
    echo "🔧 Update vite.config.js to include CSS in input array"
    ISSUES_FOUND=1
fi

# Check if blade template needs updating
if [ -f "resources/views/app.blade.php" ] && ! grep "@vite" resources/views/app.blade.php | grep -q "resources/css/app.css"; then
    echo "🔧 Update app.blade.php @vite directive to include CSS"
    ISSUES_FOUND=1
fi

if [ $ISSUES_FOUND -eq 0 ]; then
    echo -e "${GREEN}✅ No issues detected! Your Vite setup looks good.${NC}"
    echo ""
    echo "If you're still experiencing issues:"
    echo "  1. Clear browser cache (Ctrl+Shift+R / Cmd+Shift+R)"
    echo "  2. Check browser console for errors (F12)"
    echo "  3. Check web server error logs"
else
    echo ""
    echo "After fixing the issues above, restart your web server:"
    echo "  - Nginx: sudo systemctl restart nginx"
    echo "  - Apache: sudo systemctl restart apache2"
    echo "  - PHP-FPM: sudo systemctl restart php-fpm"
fi

echo ""
echo "================================================"
