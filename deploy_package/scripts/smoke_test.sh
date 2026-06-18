#!/bin/bash
# Production Smoke Test Script
# SKD CAT-BKN Application
# Usage: ./smoke_test.sh <base_url>
# Example: ./smoke_test.sh https://bimbel.bereng.info

set -e

# Configuration
BASE_URL="${1:-http://localhost/permen}"
TIMEOUT=10
FAILED_TESTS=0
PASSED_TESTS=0

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "=========================================="
echo "SKD CAT-BKN Production Smoke Test"
echo "=========================================="
echo "Base URL: $BASE_URL"
echo "Timeout: ${TIMEOUT}s"
echo "=========================================="
echo ""

# Function to test endpoint
test_endpoint() {
    local name="$1"
    local path="$2"
    local expected_code="${3:-200}"
    local method="${4:-GET}"
    
    echo -n "Testing $name... "
    
    if [ "$method" = "POST" ]; then
        response=$(curl -s -o /dev/null -w "%{http_code}" -X POST -m $TIMEOUT "$BASE_URL$path" 2>/dev/null)
    else
        response=$(curl -s -o /dev/null -w "%{http_code}" -m $TIMEOUT "$BASE_URL$path" 2>/dev/null)
    fi
    
    if [ "$response" = "$expected_code" ]; then
        echo -e "${GREEN}✓ PASS${NC} (HTTP $response)"
        ((PASSED_TESTS++))
        return 0
    else
        echo -e "${RED}✗ FAIL${NC} (Expected $expected_code, got $response)"
        ((FAILED_TESTS++))
        return 1
    fi
}

# Function to test SSL
test_ssl() {
    echo -n "Testing SSL/HTTPS... "
    
    if [[ "$BASE_URL" == https://* ]]; then
        # Check if SSL certificate is valid
        domain=$(echo "$BASE_URL" | sed -e 's|^[^/]*//||' -e 's|/.*$||')
        cert_check=$(echo | openssl s_client -connect "$domain:443" -servername "$domain" 2>/dev/null | openssl x509 -noout -checkend 0)
        
        if [ $? -eq 0 ]; then
            echo -e "${GREEN}✓ PASS${NC} (SSL valid)"
            ((PASSED_TESTS++))
            return 0
        else
            echo -e "${RED}✗ FAIL${NC} (SSL certificate invalid or expired)"
            ((FAILED_TESTS++))
            return 1
        fi
    else
        echo -e "${YELLOW}⚠ SKIP${NC} (Not HTTPS)"
        return 0
    fi
}

# Function to test security headers
test_security_headers() {
    echo -n "Testing security headers... "
    
    headers=$(curl -s -I -m $TIMEOUT "$BASE_URL" 2>/dev/null)
    
    has_hsts=$(echo "$headers" | grep -i "strict-transport-security" || true)
    has_xframe=$(echo "$headers" | grep -i "x-frame-options" || true)
    has_csp=$(echo "$headers" | grep -i "content-security-policy" || true)
    
    if [ -n "$has_hsts" ] && [ -n "$has_xframe" ] && [ -n "$has_csp" ]; then
        echo -e "${GREEN}✓ PASS${NC} (HSTS, X-Frame-Options, CSP present)"
        ((PASSED_TESTS++))
        return 0
    else
        echo -e "${RED}✗ FAIL${NC} (Missing security headers)"
        ((FAILED_TESTS++))
        return 1
    fi
}

# Function to test API response format
test_api_format() {
    echo -n "Testing API response format... "
    
    response=$(curl -s -m $TIMEOUT "$BASE_URL/api/get_soal.php?subtes=TIU&limit=1" 2>/dev/null)
    
    if echo "$response" | grep -q '"success"'; then
        echo -e "${GREEN}✓ PASS${NC} (Valid JSON response)"
        ((PASSED_TESTS++))
        return 0
    else
        echo -e "${RED}✗ FAIL${NC} (Invalid API response)"
        ((FAILED_TESTS++))
        return 1
    fi
}

# Run tests
echo "=== Infrastructure Tests ==="
test_ssl
test_security_headers

echo ""
echo "=== Page Load Tests ==="
test_endpoint "Homepage" "/"
test_endpoint "Login Page" "/pages/login.php"
test_endpoint "Register Page" "/pages/register.php"
test_endpoint "User Dashboard" "/pages/user_dashboard.php"
test_endpoint "Admin Dashboard" "/pages/admin_dashboard.php"
test_endpoint "Tryout Page" "/pages/tryout.php"
test_endpoint "Latihan Page" "/pages/latihan.php"
test_endpoint "Materi Page" "/pages/materi.php"
test_endpoint "Leaderboard" "/pages/leaderboard.php"

echo ""
echo "=== API Endpoint Tests ==="
test_endpoint "Get Soal API" "/api/get_soal.php?subtes=TIU&limit=1"
test_endpoint "Get Materi API" "/api/get_materi.php?subtes=TWK"
test_endpoint "Get Leaderboard API" "/api/get_leaderboard.php"
test_endpoint "Get Tryout Sessions API" "/api/get_tryout_sessions.php"

echo ""
echo "=== API Format Tests ==="
test_api_format

echo ""
echo "=========================================="
echo "Test Results"
echo "=========================================="
echo -e "Passed: ${GREEN}$PASSED_TESTS${NC}"
echo -e "Failed: ${RED}$FAILED_TESTS${NC}"
echo "Total: $((PASSED_TESTS + FAILED_TESTS))"
echo "=========================================="

if [ $FAILED_TESTS -eq 0 ]; then
    echo -e "${GREEN}✓ All tests passed!${NC}"
    exit 0
else
    echo -e "${RED}✗ Some tests failed. Please review the output above.${NC}"
    exit 1
fi
