#!/bin/bash

################################################################################
#
# Git Operations Skill
#
# Performs common git workflows: commit staged changes, merge to main, push,
# and sync to dev server.
#
# Usage:
#   bash scripts/git-ops.sh <branch-name> <commit-message> [OPTIONS]
#
# Examples:
#   bash scripts/git-ops.sh feature/teams-ui "fix: dialog rendering issue"
#   bash scripts/git-ops.sh feature/teams-ui "feat: add member search" --no-sync
#   bash scripts/git-ops.sh feature/teams-ui "fix: bug" --push-branch
#   bash scripts/git-ops.sh feature/teams-ui "feat: add search" --selective
#
# Options:
#   --no-sync        Skip the dev server sync (default: runs sync)
#   --push-branch    Push the feature branch to remote before merge
#   --selective      Interactively select files to stage (default: stage all)
#
# Commit Message Format (Conventional Commits):
#   feat: new feature
#   fix: bug fix
#   docs: documentation
#   style: formatting
#   refactor: code restructuring
#   perf: performance improvements
#   test: test updates
#   chore: maintenance tasks
#
################################################################################

set -e

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Validate arguments
if [ $# -lt 2 ]; then
  echo -e "${RED}Error: Missing required arguments${NC}"
  echo "Usage: bash scripts/git-ops.sh <branch-name> <commit-message> [OPTIONS]"
  echo ""
  echo "Examples:"
  echo "  bash scripts/git-ops.sh feature/teams-ui \"fix: dialog rendering issue\""
  echo "  bash scripts/git-ops.sh feature/teams-ui \"feat: add member search\" --no-sync"
  exit 1
fi

BRANCH_NAME="$1"
COMMIT_MESSAGE="$2"
SKIP_SYNC=false
PUSH_BRANCH=false
SELECTIVE_STAGING=false

# Parse optional flags
shift 2
while [ $# -gt 0 ]; do
  case "$1" in
    --no-sync)
      SKIP_SYNC=true
      ;;
    --push-branch)
      PUSH_BRANCH=true
      ;;
    --selective)
      SELECTIVE_STAGING=true
      ;;
    *)
      echo -e "${RED}Error: Unknown option '$1'${NC}"
      exit 1
      ;;
  esac
  shift
done

# Verify we're in a git repository and normalize to repo root
if ! git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
  echo -e "${RED}Error: Not in a git repository${NC}"
  exit 1
fi

REPO_ROOT=$(git rev-parse --show-toplevel)
cd "$REPO_ROOT"

CURRENT_BRANCH=$(git branch --show-current)

# Helper function to validate commit message format
validate_commit_message() {
  local message="$1"
  local pattern='^(feat|fix|docs|style|refactor|perf|test|chore)(\(.+\))?: .+'
  
  if ! [[ "$message" =~ $pattern ]]; then
    echo -e "${RED}Error: Commit message does not follow Conventional Commits format${NC}"
    echo "Expected format: <type>(<scope>): <description>"
    echo "Examples:"
    echo "  feat: add user authentication"
    echo "  fix(teams): resolve member loading issue"
    echo "  docs: update README"
    return 1
  fi
  return 0
}

# Helper function to handle errors
handle_error() {
  local error_msg="$1"
  echo -e "${RED}✗ ${error_msg}${NC}"
  echo ""
  echo -e "${YELLOW}Attempting to return to original branch...${NC}"
  git checkout "$CURRENT_BRANCH" 2>/dev/null || echo -e "${RED}⚠ Could not return to $CURRENT_BRANCH${NC}"
  exit 1
}

echo -e "${BLUE}════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}Git Operations${NC}"
echo -e "${BLUE}════════════════════════════════════════════════════════════${NC}"
echo ""

# Step 0: Validate remotes and branches
echo -e "${YELLOW}[0/6]${NC} Validating git configuration..."

# Check if origin remote exists
if ! git remote get-url origin &>/dev/null; then
  handle_error "Remote 'origin' not found. Please configure your remote repository."
fi
echo -e "${GREEN}✓ Remote 'origin' exists${NC}"

# Check if main branch exists
if ! git rev-parse --verify main &>/dev/null; then
  handle_error "Main branch does not exist. Please create or checkout main branch first."
fi
echo -e "${GREEN}✓ Main branch exists${NC}"

# Check if feature branch exists
if ! git rev-parse --verify "$BRANCH_NAME" &>/dev/null; then
  handle_error "Feature branch '$BRANCH_NAME' does not exist. Please create or checkout the branch first."
fi
echo -e "${GREEN}✓ Feature branch '$BRANCH_NAME' exists${NC}"
echo ""

# Step 1: Validate commit message format
echo -e "${YELLOW}[1/6]${NC} Validating commit message format..."
if ! validate_commit_message "$COMMIT_MESSAGE"; then
  exit 1
fi
echo -e "${GREEN}✓ Commit message is valid${NC}"
echo ""

# Step 2: Check for modified files
echo -e "${YELLOW}[2/6]${NC} Checking for modified files..."
if git diff --quiet && git diff --cached --quiet; then
  echo -e "${YELLOW}⚠ No changes to commit${NC}"
  exit 0
fi
echo -e "${GREEN}✓ Found modified files${NC}"
echo ""

# Step 3: Stage changes
echo -e "${YELLOW}[3/6]${NC} Staging changes..."
if [ "$SELECTIVE_STAGING" = true ]; then
  echo -e "${CYAN}Interactive mode: Select files to stage${NC}"
  git add -p
else
  git add -A
fi
echo -e "${GREEN}✓ Staged all changes${NC}"
echo ""

# Step 4: Commit with message
echo -e "${YELLOW}[4/6]${NC} Creating commit..."
if ! git commit -m "$COMMIT_MESSAGE"; then
  handle_error "Commit failed. Changes may already be staged or committed."
fi
echo -e "${GREEN}✓ Commit created${NC}"
echo ""

# Step 4.5: Push feature branch if requested
if [ "$PUSH_BRANCH" = true ]; then
  echo -e "${YELLOW}[4.5/6]${NC} Pushing feature branch to remote..."
  if ! git push origin "$BRANCH_NAME"; then
    handle_error "Failed to push feature branch '$BRANCH_NAME' to origin."
  fi
  echo -e "${GREEN}✓ Pushed '$BRANCH_NAME' to origin${NC}"
  echo ""
fi

# Step 5: Switch to main and merge
echo -e "${YELLOW}[5/6]${NC} Merging to main branch..."
if ! git checkout main; then
  handle_error "Failed to checkout main branch."
fi

# Attempt merge with conflict detection
if ! git merge "$BRANCH_NAME" --no-edit 2>&1; then
  echo -e "${RED}✗ Merge conflict detected!${NC}"
  echo ""
  echo -e "${YELLOW}Merge conflicts require manual resolution:${NC}"
  echo "1. Review conflicted files:"
  echo -e "   ${CYAN}git diff${NC}"
  echo "2. Edit files to resolve conflicts"
  echo "3. Stage resolved files:"
  echo -e "   ${CYAN}git add <files>${NC}"
  echo "4. Complete the merge:"
  echo -e "   ${CYAN}git commit${NC}"
  echo "5. Continue with manual push"
  echo ""
  echo -e "${YELLOW}To abort the merge:${NC}"
  echo -e "   ${CYAN}git merge --abort${NC}"
  echo ""
  git checkout "$CURRENT_BRANCH"
  exit 1
fi

echo -e "${GREEN}✓ Merged to main${NC}"
echo ""

# Step 6: Push to GitHub
echo -e "${YELLOW}[6/6]${NC} Pushing to GitHub..."
if ! git push origin main; then
  handle_error "Failed to push to origin/main. Check your network and permissions."
fi
echo -e "${GREEN}✓ Pushed to GitHub${NC}"
echo ""

# Optional: Sync to dev
if [ "$SKIP_SYNC" = false ]; then
  echo -e "${YELLOW}[Extra]${NC} Syncing to dev server..."
  if bash scripts/sync.dev.paycal.app.sh; then
    echo -e "${GREEN}✓ Dev server synced${NC}"
  else
    echo -e "${RED}⚠ Dev server sync failed (but git operations completed)${NC}"
  fi
  echo ""
else
  echo -e "${YELLOW}[Info]${NC} Skipped dev server sync (--no-sync flag)${NC}"
  echo ""
fi

echo -e "${GREEN}════════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}✓ All done!${NC}"
echo -e "${GREEN}════════════════════════════════════════════════════════════${NC}"

# Return to original branch
if ! git checkout "$CURRENT_BRANCH" 2>/dev/null; then
  echo -e "${YELLOW}⚠ Note: Could not return to branch '$CURRENT_BRANCH'${NC}"
  echo -e "${YELLOW}You are currently on: $(git branch --show-current)${NC}"
fi
