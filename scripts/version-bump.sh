#!/usr/bin/env bash

###############################################################################
# version-bump.sh
#
# Automated version bumping and changelog update script
#
# Usage:
#   ./scripts/version-bump.sh <commit-message-file>
#
# The commit message file should contain:
#   Line 1: Short title/summary
#   Line 2: (blank or technical detail)
#   Line 3+: Detailed description with bullet points
#
# This script will:
#   1. Bump VERSION (minor version: 1.XXX.000 → 1.XXX+1.000)
#   2. Update docs/CHANGELOG.md (high-level summary)
#   3. Update docs/v1.changelog.md (detailed technical notes)
#   4. Update README.md (Recent Release Highlights section)
#   5. Commit everything with the provided message
#
###############################################################################

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

# Color output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Parse arguments
if [ $# -ne 1 ]; then
    echo -e "${RED}Error: Missing commit message file${NC}"
    echo "Usage: $0 <commit-message-file>"
    echo ""
    echo "Example:"
    echo "  $0 /tmp/commit-msg.txt"
    echo "  $0 tools/commit-msg-feature.txt"
    exit 1
fi

COMMIT_MSG_FILE="$1"

if [ ! -f "$COMMIT_MSG_FILE" ]; then
    echo -e "${RED}Error: Commit message file not found: $COMMIT_MSG_FILE${NC}"
    exit 1
fi

cd "$PROJECT_ROOT"

# Read current version
if [ ! -f VERSION ]; then
    echo -e "${RED}Error: VERSION file not found${NC}"
    exit 1
fi

CURRENT_VERSION=$(cat VERSION | tr -d '\n')
echo -e "${BLUE}Current version: ${CURRENT_VERSION}${NC}"

# Parse version (format: 1.XXX.000)
if [[ ! "$CURRENT_VERSION" =~ ^1\.([0-9]{3})\.000$ ]]; then
    echo -e "${RED}Error: Invalid version format in VERSION file: $CURRENT_VERSION${NC}"
    echo "Expected format: 1.XXX.000"
    exit 1
fi

MINOR_VERSION="${BASH_REMATCH[1]}"
# Remove leading zeros for arithmetic
MINOR_NUM=$((10#$MINOR_VERSION))
NEW_MINOR_NUM=$((MINOR_NUM + 1))
# Format back to 3 digits
NEW_MINOR_VERSION=$(printf "%03d" $NEW_MINOR_NUM)
NEW_VERSION="1.${NEW_MINOR_VERSION}.000"

echo -e "${GREEN}New version: ${NEW_VERSION}${NC}"

# Get current date
RELEASE_DATE=$(date +%Y-%m-%d)

# Parse commit message file
COMMIT_TITLE=$(head -n 1 "$COMMIT_MSG_FILE" | sed 's/^[[:space:]]*//;s/[[:space:]]*$//')
COMMIT_BODY=$(tail -n +2 "$COMMIT_MSG_FILE" | sed '/^$/d')

if [ -z "$COMMIT_TITLE" ]; then
    echo -e "${RED}Error: Commit message file is empty or has no title${NC}"
    exit 1
fi

echo -e "${BLUE}Release title: ${COMMIT_TITLE}${NC}"
echo ""

# Backup files before modification
cp VERSION VERSION.backup
cp docs/CHANGELOG.md docs/CHANGELOG.md.backup
cp docs/v1.changelog.md docs/v1.changelog.md.backup
cp README.md README.md.backup

echo -e "${YELLOW}Creating backups...${NC}"

# Function to restore backups on error
restore_backups() {
    echo -e "${YELLOW}Restoring backups due to error...${NC}"
    mv VERSION.backup VERSION
    mv docs/CHANGELOG.md.backup docs/CHANGELOG.md
    mv docs/v1.changelog.md.backup docs/v1.changelog.md
    mv README.md.backup README.md
}

trap restore_backups ERR

###############################################################################
# 1. Update VERSION file
###############################################################################
echo -e "${BLUE}Updating VERSION file...${NC}"
echo "$NEW_VERSION" > VERSION

###############################################################################
# 2. Update docs/CHANGELOG.md (high-level)
###############################################################################
echo -e "${BLUE}Updating docs/CHANGELOG.md...${NC}"

# Create entry in a temp file
CHANGELOG_ENTRY_FILE=$(mktemp)
echo "### [${NEW_VERSION}] - ${RELEASE_DATE}" > "$CHANGELOG_ENTRY_FILE"
echo "**${COMMIT_TITLE}**" >> "$CHANGELOG_ENTRY_FILE"
if [ -n "$COMMIT_BODY" ]; then
    echo "$COMMIT_BODY" >> "$CHANGELOG_ENTRY_FILE"
fi

# Insert after "## Version 1.x" line
awk '
    /^## Version 1\.x/ {
        print
        print ""
        system("cat '"$CHANGELOG_ENTRY_FILE"'")
        print ""
        next
    }
    { print }
' docs/CHANGELOG.md.backup > docs/CHANGELOG.md

rm -f "$CHANGELOG_ENTRY_FILE"

###############################################################################
# 3. Update docs/v1.changelog.md (detailed technical)
###############################################################################
echo -e "${BLUE}Updating docs/v1.changelog.md...${NC}"

# Create entry in a temp file
V1_CHANGELOG_ENTRY_FILE=$(mktemp)
echo "---" > "$V1_CHANGELOG_ENTRY_FILE"
echo "" >> "$V1_CHANGELOG_ENTRY_FILE"
echo "### [${NEW_VERSION}] - ${RELEASE_DATE}" >> "$V1_CHANGELOG_ENTRY_FILE"
echo "## ${COMMIT_TITLE}" >> "$V1_CHANGELOG_ENTRY_FILE"
if [ -n "$COMMIT_BODY" ]; then
    echo "" >> "$V1_CHANGELOG_ENTRY_FILE"
    echo "$COMMIT_BODY" >> "$V1_CHANGELOG_ENTRY_FILE"
fi

# Insert after "## Version 1.x" line
awk '
    /^## Version 1\.x/ {
        print
        print ""
        system("cat '"$V1_CHANGELOG_ENTRY_FILE"'")
        next
    }
    { print }
' docs/v1.changelog.md.backup > docs/v1.changelog.md

rm -f "$V1_CHANGELOG_ENTRY_FILE"

###############################################################################
# 4. Update README.md (Recent Release Highlights)
###############################################################################
echo -e "${BLUE}Updating README.md...${NC}"

# Create README entry in a temp file
README_ENTRY_FILE=$(mktemp)
echo "### v${NEW_VERSION} (${RELEASE_DATE}) - ${COMMIT_TITLE}" > "$README_ENTRY_FILE"
echo "" >> "$README_ENTRY_FILE"
echo "**Release Focus:** ${COMMIT_TITLE}" >> "$README_ENTRY_FILE"
echo "" >> "$README_ENTRY_FILE"
if [ -n "$COMMIT_BODY" ]; then
    echo "$COMMIT_BODY" >> "$README_ENTRY_FILE"
    echo "" >> "$README_ENTRY_FILE"
fi
echo "See \`docs/CHANGELOG.md\` and \`docs/v1.changelog.md\` for concise technical release notes." >> "$README_ENTRY_FILE"

# Replace the first release highlights entry
awk '
    /^## Recent Release Highlights/ {
        print
        print ""
        system("cat '"$README_ENTRY_FILE"'")
        print ""
        # Skip until next heading (### or ##)
        in_old_release = 1
        next
    }
    in_old_release && /^###/ {
        in_old_release = 0
    }
    !in_old_release { print }
' README.md.backup > README.md

rm -f "$README_ENTRY_FILE"

###############################################################################
# 5. Git commit
###############################################################################
echo -e "${YELLOW}Staging changes...${NC}"
git add VERSION docs/CHANGELOG.md docs/v1.changelog.md README.md

echo -e "${YELLOW}Committing changes...${NC}"
COMMIT_MESSAGE="Release ${NEW_VERSION}: ${COMMIT_TITLE}

${COMMIT_BODY}

Version bump: ${CURRENT_VERSION} → ${NEW_VERSION}
Release date: ${RELEASE_DATE}

Updated files:
- VERSION
- docs/CHANGELOG.md
- docs/v1.changelog.md
- README.md"

git commit -m "$COMMIT_MESSAGE"

# Clean up backups on success
rm -f VERSION.backup docs/CHANGELOG.md.backup docs/v1.changelog.md.backup README.md.backup

echo ""
echo -e "${GREEN}✓ Version bump complete!${NC}"
echo -e "${GREEN}✓ Released ${NEW_VERSION} (${RELEASE_DATE})${NC}"
echo ""
echo -e "${BLUE}Changes committed:${NC}"
git log -1 --oneline
echo ""

# Create git tag
echo -e "${YELLOW}Creating git tag...${NC}"
TAG_MESSAGE="Release ${NEW_VERSION}: ${COMMIT_TITLE}

${COMMIT_BODY}

Released: ${RELEASE_DATE}"

git tag -a "v${NEW_VERSION}" -m "$TAG_MESSAGE"

echo -e "${GREEN}✓ Created tag v${NEW_VERSION}${NC}"
echo ""
echo -e "${YELLOW}To push changes and tag to remote:${NC}"
echo -e "  git push && git push origin v${NEW_VERSION}"
echo -e "${YELLOW}Or push everything at once:${NC}"
echo -e "  git push --follow-tags"
