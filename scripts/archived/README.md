# Archived Scripts

This folder contains scripts that are no longer actively used in the project. They have been archived for reference but are not part of the current development workflow.

## Categories of Archived Scripts

### Test Scripts
- `test-teams-api.sh` - Teams API testing utility
- `test-teams-flow.sh` - Teams workflow testing  
- `test-teams-comprehensive.sh` - Comprehensive teams testing suite
- `test-sites-quick.sh` - Quick sites testing utility
- `test_taxes_values.php` - Tax calculation value testing
- `test_sites_redis.php` - Sites Redis integration testing

### Development Setup & Utilities
- `setup-redis-sync-sudo.sh` - Redis sync sudo configuration
- `mount-paycal.sh` - Development environment mounting
- `unmount-paycal.sh` - Development environment unmounting
- `install-git-hooks.sh` - Git hook installation utility
- `git-ops-helper.sh` - Git operations shell function wrapper

### Data & Seed Scripts
- `seed_mock_earnings_test_namespace.php` - Mock test data generation
- `redis-import.sh` - Redis data import utility
- `generate_ed25519_key.php` - Key generation utility (one-time use)

### Private Local Scripts (Moved)
- Copilot bootstrap scripts with local credentials/paths were moved to a private ignored location under `copilot-scripts/private-archived/`.
- They are intentionally excluded from repository tracking.

### Analysis & Validation
- `code-metrics.php` - Code metrics analysis tool
- `extract-classes.php` - Class extraction refactoring utility
- `performance_test_config_reduction.php` - Performance testing suite (completed project)
- `validate_templates.php` - Template validation utility
- `load_html_strings.php` - HTML string caching utility
- `check_strict_header.php` - HTTP header validation
- `check-php-syntax.sh` - PHP syntax checking
- `run_phpstan.sh` - PHPStan static analysis runner

### Utilities
- `search_and_replace.sh` - Find & replace utility (superseded by `better_replace.sh`)
- `capture_js_errors.js` - JavaScript error capture script
- `local.paycal.redis-sync.plist` - macOS launchd plist configuration

## Active Scripts

The following scripts remain in the main scripts directory as they are actively maintained and used:

- `better_replace.sh` - Advanced find/replace utility
- `check-envs.sh` - Environment configuration validation
- `git-ops.sh` - Git operations wrapper
- `project_stats.py` - Project statistics generator (referenced in README)
- `push-and-sync.sh` - Git push + sync wrapper
- `redis-sync.sh` - Bidirectional Redis sync system
- `redis-update.sh` - Redis template/string imports
- `sync.dev.paycal.app.sh` - Development sync script
- `sync.paycal.app.sh` - Production sync script
- `install-redis-sync.sh` - Redis sync installation
- `sdp.env` - Environment configuration

## Notes

- These scripts are preserved for historical reference and can be restored if needed
- Test scripts should be replaced with PHPUnit tests in `dev/html/tests/`
- One-time utilities should be recreated if needed rather than reused
