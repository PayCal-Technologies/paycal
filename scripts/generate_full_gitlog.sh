#!/usr/bin/env bash

OUTFILE="repo_commit_history_$(date +%F).txt"

git log \
  --date=iso \
  --pretty=format:"============================================================
Commit: %H
Short:  %h
Author: %an <%ae>
Date:   %ad
Parent: %P
Refs:   %D

Subject:
%s

Message:
%B
" > "$OUTFILE"

echo "Commit history written to $OUTFILE"
