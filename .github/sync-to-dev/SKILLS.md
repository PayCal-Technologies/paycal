# PayCal Development Skills & Workflow

## Versioning and Tagging

### Semantic Versioning
PayCal follows [Semantic Versioning](https://semver.org/): `MAJOR.MINOR.PATCH`

- **MAJOR** (first number): Breaking changes, decided by product owner
- **MINOR** (second number): New features that are backward compatible
- **PATCH** (third number): Bug fixes and small improvements

### Current Version
- Latest released version: 1.6.18
- Current development version: 1.7.0 (tagged for Sites page AJAX features)

### Automatic Tagging Guidelines
When committing changes, automatically determine version bump based on commit scope:

- **PATCH (1.6.19)**: Bug fixes, syntax errors, small improvements
  - Examples: "Fix PHP syntax error", "Fix undefined error", "Fix RequestGuard"
  
- **MINOR (1.7.0)**: New features, enhancements
  - Examples: "Implement full AJAX for Sites page", "Add bulk actions dropdown"
  
- **MAJOR (2.0.0)**: Breaking changes (rare, decided by product owner)

### Tagging Process
1. After significant commits, create and push version tag:
   ```bash
   git tag 1.7.0
   git push origin 1.7.0
   ```
2. Update CHANGELOG.md with version details
3. For production releases, use sync.paycal.app.sh

## Git Workflow for Deployment

### Standard Commit and Push to Main
```bash
git add .
git commit -m "Descriptive commit message"
git push origin main
```

### Sync to Development Environment
After pushing to main, always sync to dev.paycal.app:
```bash
./scripts/sync.dev.paycal.app.sh
```

### Sync to Production Environment
For production deployments:
```bash
./scripts/sync.paycal.app.sh
```

## PHP Syntax Checking

### Check All PHP Files for Syntax Errors
Before committing, run syntax checks:
```bash
find . -name "*.php" -exec php -l {} \;
```

### Check Specific File
```bash
php -l path/to/file.php
```

## Reminders
- Always run PHP syntax checks before committing
- Sync to dev environment after pushing to main
- Test changes on dev.paycal.app before production sync
- Use descriptive commit messages