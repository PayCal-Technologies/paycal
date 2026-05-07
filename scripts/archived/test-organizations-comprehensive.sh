#!/bin/bash

################################################################################
# PayCal Teams API Comprehensive Test Flow
#
# This script tests the complete teams functionality:
# - Team listing
# - Team creation
# - Team deletion
# - Member operations (when additional test users are available)
# - Error handling and edge cases
#
# The tests are organized in a modular way to allow easy addition of new tests
# as the team features are developed.
#
# Usage: bash scripts/test-teams-comprehensive.sh
#
# To extend:
# 1. Output structured test results (JSON)
# 2. Add member operation tests  
# 3. Add permission/authorization tests
# 4. Add error case tests
# 5. Add concurrent operation tests
################################################################################

set -e

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

# Configuration
DOMAIN="mac.paycal.local"
API_BASE="http://$DOMAIN/api"
COOKIES_FILE="/tmp/paycal_copilot_cookies.txt"
TEST_RESULTS_FILE="/tmp/teams_test_results.json"

# Test counters
TESTS_RUN=0
TESTS_PASSED=0
TESTS_FAILED=0

# Helper function to make API requests
api_request() {
    local method=$1
    local endpoint=$2
    local data=$3
    
    if [ "$method" == "GET" ]; then
        curl -s -b "$COOKIES_FILE" -X GET "$API_BASE$endpoint" 2>&1 | grep -v "^$"
    elif [ "$method" == "POST" ]; then
        if [ -z "$data" ]; then
            curl -s -b "$COOKIES_FILE" -X POST "$API_BASE$endpoint" \
                -H 'Content-Type: application/x-www-form-urlencoded' 2>&1 | grep -v "^$"
        else
            curl -s -b "$COOKIES_FILE" -X POST "$API_BASE$endpoint" \
                -H 'Content-Type: application/x-www-form-urlencoded' \
                -d "$data" 2>&1 | grep -v "^$"
        fi
    fi
}

# Helper function to extract status
get_status() {
    echo "$1" | grep -o '"status":"[^"]*"' | cut -d'"' -f4 || echo "unknown"
}

# Helper function to extract message
get_message() {
    echo "$1" | grep -o '"message":"[^"]*"' | cut -d'"' -f4 || echo "No message"
}

# Test execution function
run_test() {
    local test_name=$1
    local test_body=$2
    
    ((TESTS_RUN++))
    
    if eval "$test_body"; then
        echo -e "${GREEN}✓ PASS${NC}: $test_name"
        ((TESTS_PASSED++))
        return 0
    else
        echo -e "${RED}✗ FAIL${NC}: $test_name"
        ((TESTS_FAILED++))
        return 1
    fi
}

echo -e "${BLUE}════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}PAYC AL TEAMS API - COMPREHENSIVE TEST FLOW${NC}"
echo -e "${BLUE}════════════════════════════════════════════════════════════${NC}"
echo ""

# Check if cookies file exists
if [ ! -f "$COOKIES_FILE" ]; then
    echo -e "${RED}✗ Cookies file not found: $COOKIES_FILE${NC}"
    echo "Run: bash scripts/setup-copilot-user.sh"
    exit 1
fi

echo "Test Configuration:"
echo "  Domain: $DOMAIN"
echo "  API Base: $API_BASE"
echo "  Cookies: $COOKIES_FILE"
echo ""

# ============================================================================
# TEST SUITE 1: TEAM LISTING
# ============================================================================
echo -e "${CYAN}TEST SUITE 1: Team Listing${NC}"
echo "─────────────────────────────────────────────────────────────"

test_list_teams() {
    RESPONSE=$(api_request "GET" "/teams")
    STATUS=$(get_status "$RESPONSE")
    [ "$STATUS" == "success" ] && echo "$RESPONSE" | grep -q '"teams"'
}

run_test "List teams (should return success)" "test_list_teams"
echo ""

# ============================================================================
# TEST SUITE 2: TEAM CREATION
# ============================================================================
echo -e "${CYAN}TEST SUITE 2: Team Creation${NC}"
echo "─────────────────────────────────────────────────────────────"

test_create_team() {
    TEAM_NAME="Test Team $(date +%s)"
    RESPONSE=$(api_request "POST" "/teams/create" "team_name=$(echo -n "$TEAM_NAME" | jq -sRr @uri)")
    STATUS=$(get_status "$RESPONSE")
    [ "$STATUS" == "success" ] && echo "$RESPONSE" | grep -q '"teamID"'
}

run_test "Create a team" "test_create_team"

test_create_team_with_special_chars() {
    TEAM_NAME="Team & Co. (2026)"
    RESPONSE=$(api_request "POST" "/teams/create" "team_name=$(echo -n "$TEAM_NAME" | jq -sRr @uri)")
    STATUS=$(get_status "$RESPONSE")
    [ "$STATUS" == "success" ]
}

run_test "Create team with special characters" "test_create_team_with_special_chars"
echo ""

# ============================================================================
# TEST SUITE 3: TEAM DETAILS & MEMBERS
# ============================================================================
echo -e "${CYAN}TEST SUITE 3: Team Details & Members${NC}"
echo "─────────────────────────────────────────────────────────────"

# Create a test team first
ORGANIZATION_NAME="Detail Test $(date +%s)"
RESPONSE=$(api_request "POST" "/teams/create" "team_name=$(echo -n "$ORGANIZATION_NAME" | jq -sRr @uri)")
TEST_ORGANIZATION_ID=$(echo "$RESPONSE" | grep -o '"teamID":"[^"]*"' | cut -d'"' -f4)

if [ -z "$TEST_ORGANIZATION_ID" ]; then
    echo -e "${YELLOW}⊘ Skipping team detail tests (team creation failed)${NC}"
else
    test_get_team_members() {
        RESPONSE=$(api_request "GET" "/teams/$TEST_ORGANIZATION_ID/members")
        STATUS=$(get_status "$RESPONSE")
        [ "$STATUS" == "success" ]
    }
    
    run_test "Get team members" "test_get_team_members"
fi
echo ""

# ============================================================================
# TEST SUITE 4: TEAM DELETION
# ============================================================================
echo -e "${CYAN}TEST SUITE 4: Team Deletion${NC}"
echo "─────────────────────────────────────────────────────────────"

# Create a team specifically for deletion test
DELETE_ORGANIZATION_NAME="Delete Test $(date +%s)"
RESPONSE=$(api_request "POST" "/teams/create" "team_name=$(echo -n "$DELETE_ORGANIZATION_NAME" | jq -sRr @uri)")
DELETE_ORGANIZATION_ID=$(echo "$RESPONSE" | grep -o '"teamID":"[^"]*"' | cut -d'"' -f4)

if [ -z "$DELETE_ORGANIZATION_ID" ]; then
    echo -e "${YELLOW}⊘ Skipping deletion test (team creation failed)${NC}"
else
    test_delete_team() {
        RESPONSE=$(api_request "POST" "/teams/delete" "team_id=$DELETE_ORGANIZATION_ID")
        STATUS=$(get_status "$RESPONSE")
        [ "$STATUS" == "success" ]
    }
    
    run_test "Delete a team" "test_delete_team"
    
    # Verify deletion
    test_verify_deletion() {
        RESPONSE=$(api_request "GET" "/teams")
        ! echo "$RESPONSE" | grep -q "\"teamID\":\"$DELETE_ORGANIZATION_ID\""
    }
    
    run_test "Verify team deletion" "test_verify_deletion"
fi
echo ""

# ============================================================================
# FUTURE TEST SUITES (Placeholders)
# ============================================================================
echo -e "${CYAN}TEST SUITES FOR FUTURE IMPLEMENTATION${NC}"
echo "─────────────────────────────────────────────────────────────"
echo -e "${YELLOW}⊘ Add Member Tests${NC}"
echo "   - Add single member"
echo "   - Add multiple members (batching)"
echo "   - Add duplicate member (should fail)"
echo "   - Add non-existent user (should fail)"
echo ""
echo -e "${YELLOW}⊘ Member Role Tests${NC}"
echo "   - Promote member to manager"
echo "   - Demote manager"
echo "   - Manager cannot demote sole manager"
echo ""
echo -e "${YELLOW}⊘ Remove Member Tests${NC}"
echo "   - Remove member from team"
echo "   - Remove manager (should fail if sole manager)"
echo "   - Remove non-existent member (should fail)"
echo ""
echo -e "${YELLOW}⊘ Authorization Tests${NC}"
echo "   - Non-manager cannot add members"
echo "   - Non-manager cannot remove members"
echo "   - Non-manager cannot delete team"
echo "   - Member can view team details"
echo "   - Non-member cannot view private team details"
echo ""
echo -e "${YELLOW}⊘ Error Cases${NC}"
echo "   - Create team with empty name"
echo "   - Create team with very long name"
echo "   - Delete non-existent team"
echo "   - Access non-existent team"
echo ""
echo -e "${YELLOW}⊘ Concurrent Operations${NC}"
echo "   - Multiple member additions in parallel"
echo "   - Team modification during member deletion"
echo ""

# ============================================================================
# TEST SUMMARY
# ============================================================================
echo -e "${BLUE}════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}TEST SUMMARY${NC}"
echo -e "${BLUE}════════════════════════════════════════════════════════════${NC}"
echo ""
echo "Total Tests Run:  $TESTS_RUN"
echo -e "Passed:           ${GREEN}$TESTS_PASSED${NC}"
echo -e "Failed:           ${RED}$TESTS_FAILED${NC}"
echo ""

if [ $TESTS_FAILED -eq 0 ]; then
    echo -e "${GREEN}✓ All tests passed!${NC}"
    exit 0
else
    echo -e "${RED}✗ Some tests failed${NC}"
    exit 1
fi
