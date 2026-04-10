---
mode: agent
description: 'Run the PayCal source dump generator, print absolute output path, and reveal the file in Finder (macOS). Trigger: /gsc.'
---

Execute this workflow in `<REPO_ROOT>`:

1. Run `php scripts/generate-source-dump.php`.
2. Determine the generated output path from command output (default pattern `/var/www/paycal/paycal-source-dump-as-at-YYYY-MM-DD.txt`).
3. Reveal the file in Finder with `open -R <path>`.
4. Respond with:
   - `Output file: <absolute-path>`
   - `Finder reveal: success|failed`
   - If failed, a one-line reason.
   - Keep response concise and verification-focused (no filler).
