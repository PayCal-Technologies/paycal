---
name: gsc
description: 'Generate the PayCal source dump artifact with security gate context. Use for /gsc, generate source dump, audit artifact export, and security review handoff.'
argument-hint: 'Optional output path override'
user-invocable: true
disable-model-invocation: true
---

# Generate Source Dump

Run the PayCal source dump generator and return an audit-ready artifact path.

## Required Steps

1. Run from repo root:
   - `php scripts/generate-source-dump.php`
2. Parse output and extract the generated dump path.
   - Default path pattern: `/var/www/paycal/paycal-source-dump-as-at-YYYY-MM-DD.txt`
3. In the chat response, always print:
   - `Output file: <absolute-path>`
4. If running on macOS and Finder is available, reveal the file immediately:
   - `open -R <absolute-path>`

## Required Response Contract

- Include an `Output file:` line with the absolute path.
- Confirm whether Finder reveal succeeded.
- If generation fails, include the failing command and first actionable error.
- Keep output precise: avoid filler, state assumptions only when needed, and include one quick verification line when possible.

## Language Constraint

- Do not use Python for this skill workflow.
- If auxiliary automation is needed, prefer PHP, shell, or Rust.
