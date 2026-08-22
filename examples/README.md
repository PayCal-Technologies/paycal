# PayCal Public Examples

This directory documents safe, public examples for local development, testing, and repository evaluation. Examples should use fake data only and should never require production credentials, real worker records, live business exports, or customer data.

## Local Setup Example

```bash
git clone https://github.com/PayCal-Technologies/paycal.git paycal
cd paycal
composer install
npm install
cp html/.env.example html/.env
```

Use local-only environment values in `html/.env`. For payment, email, Redis, and browser testing, use disposable test credentials and fake data.

## Developer Verification Example

```bash
composer run test:quick
composer run phpstan:strict
composer run format:check
npm run test:js
```

Use this for documentation-only changes, public setup checks, and small behavior changes. For larger changes, add relevant PHPUnit, Playwright, accessibility, web-vitals, or security gates.

## Accessibility Verification Example

```bash
npm run test:a11y:playwright:suite
npm run test:a11y:contrast
npm run test:a11y:reflow
```

Use accessibility gates when changing navigation, dialogs, datagrids, forms, themes, keyboard behavior, live regions, or page structure.

## Privacy-Safe Workflow Examples

Worker workflow:

- Create fake local work entries.
- Confirm hours, sites, wages, forecasts, and exports render as expected.
- Avoid using real employer names, pay rates, addresses, or identifying records in screenshots.

Business workflow:

- Use fake organizations, fake members, and fake reports.
- Verify consent, protected access, reporting, export, audit, revocation, and cache-purge behavior.
- Do not publish real business or payroll-adjacent data.

Security workflow:

- Reproduce locally with disposable accounts and fake records.
- Stop if another user's data, a real secret, or production material appears.
- Report suspected vulnerabilities privately through `security@paycal.app`.

## Useful Links

- Product: https://paycal.app/
- Company: https://paycaltech.com/
- GitHub: https://github.com/PayCal-Technologies/paycal
- Security policy: ../SECURITY.md
- Contributor guide: ../CONTRIBUTING.md
