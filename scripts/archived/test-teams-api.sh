#!/bin/bash

################################################################################
# Team API Testing Script
# 
# Tests team creation, member management, and other operations
# Uses curl to mock HTTP requests to the teams API
################################################################################

set -e

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
API_BASE="http://localhost:3000/api"
HEADERS_JSON='Content-Type: application/json'
HEADERS_FORM='Content-Type: application/x-www-form-urlencoded'

echo -e "${BLUE}════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}Teams API Test Suite${NC}"
echo -e "${BLUE}════════════════════════════════════════════════════════════${NC}"
echo ""

# Test 1: Unauthenticated request (should fail)
echo -e "${YELLOW}Test 1: Unauthenticated request (should fail)${NC}"
echo "GET $API_BASE/teams"
RESPONSE=$(curl -s -w "\n%{http_code}" -X GET "$API_BASE/teams")
HTTP_CODE=$(echo "$RESPONSE" | tail -n 1)
BODY=$(echo "$RESPONSE" | head -n -1)
echo -e "Status: ${YELLOW}$HTTP_CODE${NC}"
echo "Response: $BODY"
echo ""

# Test 2: Create team without authentication (should fail)
echo -e "${YELLOW}Test 2: Create team without auth (should fail)${NC}"
echo "POST $API_BASE/teams/create"
echo "Data: team_name=Test Team"
RESPONSE=$(curl -s -w "\n%{http_code}" -X POST "$API_BASE/teams/create" \
  -H "$HEADERS_FORM" \
  -d "team_name=Test%20Team")
HTTP_CODE=$(echo "$RESPONSE" | tail -n 1)
BODY=$(echo "$RESPONSE" | head -n -1)
echo -e "Status: ${YELLOW}$HTTP_CODE${NC}"
echo "Response: $BODY"
echo ""

# Test 3: Check if the endpoint is routable
echo -e "${YELLOW}Test 3: Check root API endpoint${NC}"
echo "GET $API_BASE/"
RESPONSE=$(curl -s -w "\n%{http_code}" -X GET "$API_BASE/")
HTTP_CODE=$(echo "$RESPONSE" | tail -n 1)
BODY=$(echo "$RESPONSE" | head -n -1)
echo -e "Status: ${YELLOW}$HTTP_CODE${NC}"
echo "Response: $BODY"
echo ""

# Test 4: Check server connectivity
echo -e "${YELLOW}Test 4: Server connectivity${NC}"
if nc -z localhost 3000 2>/dev/null; then
  echo -e "${GREEN}✓ Server is running on port 3000${NC}"
else
  echo -e "${RED}✗ Cannot connect to server on port 3000${NC}"
fi
echo ""

echo -e "${BLUE}════════════════════════════════════════════════════════════${NC}"
echo -e "${YELLOW}Tests complete. Check responses above for issues.${NC}"
echo -e "${BLUE}════════════════════════════════════════════════════════════${NC}"
