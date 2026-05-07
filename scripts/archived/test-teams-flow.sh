#!/bin/bash

################################################################################
# Teams API Test Flow
#
# Tests the complete flow of teams functionality:
# 1. Load teams (should be empty initially)
# 2. Create a team
# 3. List teams again
# 4. Get team members
# 5. Add a member
# 6. List members again
# 7. Promote member
# 8. Demote member
# 9. Remove member
# 10. Delete team
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

# Helper function to extract status from response
get_status() {
    echo "$1" | jq -r '.status // "error"'
}

# Helper function to extract message from response
get_message() {
    echo "$1" | jq -r '.message // "No message"'
}

# Helper function to extract data from response
get_data() {
    echo "$1" | jq '.data // empty'
}

echo -e "${BLUE}════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}TEAMS API TEST FLOW${NC}"
echo -e "${BLUE}════════════════════════════════════════════════════════════${NC}"
echo ""

# Check if cookies file exists
if [ ! -f "$COOKIES_FILE" ]; then
    echo -e "${RED}✗ Cookies file not found: $COOKIES_FILE${NC}"
    echo "Run: bash scripts/setup-copilot-user.sh"
    exit 1
fi

TEAM_ID=""
MEMBER_UUID=""

# Test 1: Get initial teams list
echo -e "${YELLOW}[1/10] Getting initial teams list...${NC}"
RESPONSE=$(api_request "GET" "/teams")
STATUS=$(get_status "$RESPONSE")

if [ "$STATUS" == "success" ]; then
    echo -e "${GREEN}✓ Teams list retrieved${NC}"
    TEAMS=$(get_data "$RESPONSE" | jq '.teams | length')
    echo "  Teams count: $TEAMS"
else
    echo -e "${RED}✗ Failed to get teams:${NC}"
    echo "  $RESPONSE"
fi
echo ""

# Test 2: Create a team
echo -e "${YELLOW}[2/10] Creating a team...${NC}"
TEAM_NAME="Test Team $(date +%s)"
RESPONSE=$(api_request "POST" "/teams/create" "team_name=$(echo -n "$TEAM_NAME" | jq -sRr @uri)")
STATUS=$(get_status "$RESPONSE")

if [ "$STATUS" == "success" ]; then
    echo -e "${GREEN}✓ Team created${NC}"
    TEAM_ID=$(get_data "$RESPONSE" | jq -r '.teamID // empty')
    echo "  Team ID: $TEAM_ID"
    echo "  Team Name: $TEAM_NAME"
else
    echo -e "${RED}✗ Failed to create team:${NC}"
    echo "  Status: $STATUS"
    echo "  Message: $(get_message "$RESPONSE")"
    echo "  Full response: $RESPONSE"
fi
echo ""

# Test 3: Get teams list again
echo -e "${YELLOW}[3/10] Getting teams list after creation...${NC}"
if [ -z "$TEAM_ID" ]; then
    echo -e "${YELLOW}⊘ Skipping (team creation failed)${NC}"
else
    RESPONSE=$(api_request "GET" "/teams")
    STATUS=$(get_status "$RESPONSE")
    
    if [ "$STATUS" == "success" ]; then
        TEAMS=$(get_data "$RESPONSE" | jq '.teams | length')
        echo -e "${GREEN}✓ Teams list retrieved${NC}"
        echo "  Teams count: $TEAMS"
    else
        echo -e "${RED}✗ Failed to get teams${NC}"
    fi
fi
echo ""

# Test 4: Get team members
echo -e "${YELLOW}[4/10] Getting team members...${NC}"
if [ -z "$TEAM_ID" ]; then
    echo -e "${YELLOW}⊘ Skipping (team creation failed)${NC}"
else
    RESPONSE=$(api_request "GET" "/teams/$TEAM_ID/members")
    STATUS=$(get_status "$RESPONSE")
    
    if [ "$STATUS" == "success" ]; then
        echo -e "${GREEN}✓ Team members retrieved${NC}"
        MEMBER_COUNT=$(get_data "$RESPONSE" | jq '.memberCount // 0')
        echo "  Member count: $MEMBER_COUNT"
    else
        echo -e "${RED}✗ Failed to get team members:${NC}"
        echo "  $(get_message "$RESPONSE")"
    fi
fi
echo ""

# Note: Tests 5-9 require another user to be available
echo -e "${YELLOW}[5-9/10] Member operations...${NC}"
echo -e "${YELLOW}⊘ Skipped (requires additional test user setup)${NC}"
echo ""

# Test 10: Delete team
echo -e "${YELLOW}[10/10] Deleting team...${NC}"
if [ -z "$TEAM_ID" ]; then
    echo -e "${YELLOW}⊘ Skipping (team creation failed)${NC}"
else
    RESPONSE=$(api_request "POST" "/teams/delete" "team_id=$TEAM_ID")
    STATUS=$(get_status "$RESPONSE")
    
    if [ "$STATUS" == "success" ]; then
        echo -e "${GREEN}✓ Team deleted${NC}"
    else
        echo -e "${RED}✗ Failed to delete team:${NC}"
        echo "  $(get_message "$RESPONSE")"
    fi
fi
echo ""

echo -e "${BLUE}════════════════════════════════════════════════════════════${NC}"
echo -e "${CYAN}Test Summary${NC}"
echo -e "${BLUE}════════════════════════════════════════════════════════════${NC}"
echo ""
echo "To expand this test:"
echo "1. Add additional test users"
echo "2. Test member operations (add, promote, demote, remove)"
echo "3. Test error cases (invalid inputs, permissions, etc.)"
echo "4. Test concurrent operations"
echo ""
