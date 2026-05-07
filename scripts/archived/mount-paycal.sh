#!/bin/bash

# Mount paycal using sshfs
# Remote: root@paycal.app:/var/www/paycal/dev
# Local: /Users/cs/mnt/paycal

# Create mount point if it doesn't exist
sudo mkdir -p /Users/cs/mnt/paycal

# Mount using sshfs
# Note: May require sudo and SSH key authentication
sudo sshfs -o allow_other,defer_permissions root@paycal.app:/var/www/paycal/dev /Users/cs/mnt/paycal

echo "Mounted paycal to /Users/cs/mnt/paycal"
