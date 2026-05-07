# Formatter Policy

PayCal treats automated formatting as a constrained transformation pipeline, not as a general refactoring tool.

## Allowed Autofix

- whitespace normalization
- indentation and line endings
- import ordering and single-import splitting
- layout-only array and operator spacing
- non-semantic PHPDoc trimming

## Forbidden Autofix

- function call mutation
- control-flow rewrites
- variable inlining or removal
- statement merging
- type inference changes
- class or member reordering when it can affect tests, reflection, or review stability

## Protected Shapes

- Keep explicit guard forms such as `if (...) { return false; } return true;`.
- Keep repeated cleanup statements as separate calls when test structure depends on them.
- Keep temporary variables that carry PHPStan narrowing, especially `@var`-annotated handoff values.
- Keep function call signatures unchanged unless the change is reviewed as refactoring.

## Enforcement

- `.php-cs-fixer.php` is limited to AST-preserving rules only.
- `scripts/check-semantic-diff.php` rejects suspicious PHP diffs that look like refactoring.
- `scripts/hooks/pre-commit.sh` runs the semantic diff scan on staged PHP changes.
- GitHub Actions runs `composer run format:check`, the semantic diff scan, PHPStan, and PHPUnit.

## Review Rule

If a cleanup changes execution, type narrowing, call signatures, or test-observable structure, it is not formatting. Split it into a separate reviewed refactor.