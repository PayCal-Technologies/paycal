#!/bin/bash

################################################################################
# Git Ops Helper - Shell Function Wrapper
#
# Add this to your ~/.bashrc or ~/.zshrc to enable:
#   git-ops <branch> <message> [options]
#
# Then reload your shell:
#   source ~/.bashrc   # for bash
#   source ~/.zshrc    # for zsh
#
################################################################################

git-ops() {
  local script_path="$(git rev-parse --show-toplevel)/scripts/git-ops.sh"
  
  if [ ! -f "$script_path" ]; then
    echo "Error: git-ops.sh script not found at $script_path"
    return 1
  fi
  
  bash "$script_path" "$@"
}

# Generate a shell function to add to your profile
# Copy this to your ~/.bashrc or ~/.zshrc:
#
# git-ops() {
#   bash "$(git rev-parse --show-toplevel)/scripts/git-ops.sh" "$@"
# }
#
# Then you can use:
#   git-ops feature/myfeature "fix: my commit message"
#   git-ops feature/myfeature "feat: add feature" --no-sync
#   git-ops feature/myfeature "style: format code" --selective
