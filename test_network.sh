#!/bin/bash

echo "=== Wasender API Network Diagnostic ==="
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

API_HOST="www.wasenderapi.com"
API_URL="https://www.wasenderapi.com/api/whatsapp-sessions"

echo "Testing connection to: $API_HOST"
echo "Full API URL: $API_URL"
echo ""

# Test 1: DNS Resolution
echo "--- Test 1: DNS Resolution ---"
if host $API_HOST > /dev/null 2>&1; then
    echo -e "${GREEN}✓${NC} DNS resolution successful"
    host $API_HOST
else
    echo -e "${RED}✗${NC} DNS resolution failed"
fi
echo ""

# Test 2: Ping Test
echo "--- Test 2: Ping Test ---"
if ping -c 3 -W 2 $API_HOST > /dev/null 2>&1; then
    echo -e "${GREEN}✓${NC} Ping successful"
    ping -c 3 $API_HOST
else
    echo -e "${YELLOW}⚠${NC} Ping failed (this might be normal if ICMP is blocked)"
    ping -c 3 $API_HOST 2>&1 | tail -3
fi
echo ""

# Test 3: Port 443 (HTTPS) connectivity
echo "--- Test 3: HTTPS Port (443) Test ---"
if nc -z -w 5 $API_HOST 443 2>/dev/null; then
    echo -e "${GREEN}✓${NC} Port 443 is reachable"
else
    echo -e "${RED}✗${NC} Port 443 is NOT reachable"
    echo "This is the main issue - HTTPS connections are being blocked"
fi
echo ""

# Test 4: Traceroute
echo "--- Test 4: Traceroute (first 10 hops) ---"
if command -v traceroute &> /dev/null; then
    traceroute -m 10 -w 2 $API_HOST 2>&1 | head -15
else
    echo "Traceroute not available"
fi
echo ""

# Test 5: cURL test with timeout
echo "--- Test 5: cURL Connection Test ---"
echo "Attempting HTTPS connection (10 second timeout)..."
CURL_OUTPUT=$(curl -v --max-time 10 -s $API_URL 2>&1)
CURL_EXIT=$?

if [ $CURL_EXIT -eq 0 ]; then
    echo -e "${GREEN}✓${NC} cURL connection successful!"
    echo "Response snippet:"
    echo "$CURL_OUTPUT" | tail -10
elif [ $CURL_EXIT -eq 28 ]; then
    echo -e "${RED}✗${NC} Connection TIMEOUT"
    echo "The connection is timing out - this indicates a network/firewall issue"
elif [ $CURL_EXIT -eq 6 ]; then
    echo -e "${RED}✗${NC} Could not resolve host"
    echo "DNS resolution failed"
else
    echo -e "${RED}✗${NC} cURL failed with exit code: $CURL_EXIT"
fi
echo ""

# Test 6: Check proxy settings
echo "--- Test 6: Proxy Settings ---"
if [ -n "$HTTP_PROXY" ] || [ -n "$HTTPS_PROXY" ] || [ -n "$http_proxy" ] || [ -n "$https_proxy" ]; then
    echo "Proxy detected:"
    echo "  HTTP_PROXY: ${HTTP_PROXY:-$http_proxy}"
    echo "  HTTPS_PROXY: ${HTTPS_PROXY:-$https_proxy}"
else
    echo "No proxy configured"
fi
echo ""

# Test 7: Check firewall (macOS)
echo "--- Test 7: Firewall Status (macOS) ---"
if [ "$(uname)" == "Darwin" ]; then
    if /usr/libexec/ApplicationFirewall/socketfilterfw --getglobalstate 2>/dev/null | grep -q "enabled"; then
        echo -e "${YELLOW}⚠${NC} Firewall is ENABLED"
        echo "The firewall might be blocking outgoing connections"
    else
        echo "Firewall is disabled or status unknown"
    fi
else
    echo "Not running on macOS - skipping firewall check"
fi
echo ""

# Test 8: Test alternative connection
echo "--- Test 8: Testing Alternative Sites ---"
echo "Testing if general HTTPS connectivity works..."
if curl -s --max-time 5 https://www.google.com > /dev/null 2>&1; then
    echo -e "${GREEN}✓${NC} Can connect to Google (general internet works)"
else
    echo -e "${RED}✗${NC} Cannot connect to Google (general internet issue)"
fi

if curl -s --max-time 5 https://api.cloudflare.com/client/v4/ips > /dev/null 2>&1; then
    echo -e "${GREEN}✓${NC} Can connect to Cloudflare API"
else
    echo -e "${YELLOW}⚠${NC} Cannot connect to Cloudflare API"
    echo "Wasender uses Cloudflare - this might be related"
fi
echo ""

# Summary
echo "=== SUMMARY ==="
echo ""
if nc -z -w 5 $API_HOST 443 2>/dev/null; then
    echo -e "${GREEN}✓${NC} Network connectivity looks OK"
    echo "If you're still having issues, the problem might be:"
    echo "  - API authentication/authorization"
    echo "  - API rate limiting"
    echo "  - API service outage"
else
    echo -e "${RED}✗${NC} NETWORK CONNECTIVITY ISSUE DETECTED"
    echo ""
    echo "Recommended actions:"
    echo "  1. Check if you're behind a corporate firewall"
    echo "  2. Try connecting from a different network (mobile hotspot)"
    echo "  3. Try using a VPN"
    echo "  4. Contact your network administrator"
    echo "  5. Contact Wasender support to verify API status"
    echo ""
    echo "The issue is NOT in your Laravel code - it's a network/firewall problem."
fi
echo ""
echo "=== End of Diagnostic ==="
