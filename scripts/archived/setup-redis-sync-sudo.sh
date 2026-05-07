#!/bin/bash
# Setup passwordless sudo for Redis sync operations
# This allows the LaunchAgent to run without password prompts

set -e

echo "==========================================="
echo "Redis Sync - Passwordless Sudo Setup"
echo "==========================================="
echo
echo "This will configure sudo to allow Redis sync"
echo "operations without password prompts."
echo
echo "Commands that will run without password:"
echo "  - cp (Redis dump files)"
echo "  - chown (Redis file ownership)"
echo "  - brew services (Redis restart)"
echo
read -p "Continue? [y/N]: " -n 1 -r
echo

if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Aborted."
    exit 0
fi

# Install sudoers file
echo "Installing sudoers configuration..."
sudo install -o root -g wheel -m 0440 \
    /private/var/www/paycal/scripts/redis-sync-sudoers \
    /etc/sudoers.d/redis-sync

# Verify syntax
echo "Verifying sudoers syntax..."
sudo visudo -c -f /etc/sudoers.d/redis-sync

if [ $? -eq 0 ]; then
    echo "✓ Sudoers configuration installed successfully"
    echo
    echo "You can now run Redis sync without password prompts:"
    echo "  ./scripts/redis-sync.sh"
    echo
else
    echo "✗ Sudoers configuration has errors"
    sudo rm /etc/sudoers.d/redis-sync
    exit 1
fi
