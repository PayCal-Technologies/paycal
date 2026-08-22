# Contributing to PayCal

PayCal is a proprietary public source repository maintained by PayCal Technologies Inc. Public issues and pull requests are welcome when they improve documentation, examples, accessibility evidence, developer ergonomics, test coverage, or clearly bounded product behavior.

By submitting a pull request, issue, patch, or example, you confirm that you have the right to contribute it and that PayCal Technologies Inc. may use it in PayCal under the repository's proprietary license terms.

## Good First Contributions

- Documentation fixes that make setup, testing, security, privacy, or accessibility behavior easier to verify.
- Reproducible bug reports with exact steps, expected behavior, actual behavior, and environment details.
- Accessibility defects with affected route, keyboard path, assistive technology notes, screenshots where useful, and WCAG impact.
- Test coverage for existing public behavior, especially privacy, passkey, business-access, reporting, export, and accessibility paths.
- Examples that demonstrate safe local setup without production secrets or customer data.

## Before You Open a Pull Request

1. Search existing issues and pull requests to avoid duplicates.
2. Keep changes scoped to one concern.
3. Do not include production credentials, customer data, private worker records, API keys, Redis dumps, screenshots with live personal data, or copied third-party proprietary content.
4. Add or update tests when behavior changes.
5. Update documentation when commands, workflows, public behavior, or developer setup changes.

## Local Setup

```bash
git clone https://github.com/PayCal-Technologies/paycal.git paycal
cd paycal
composer install
npm install
cp html/.env.example html/.env
```

Configure local-only values in `html/.env`. Use disposable development accounts, local Redis databases, and test payment credentials. Never point a public contribution branch at production services.

## Useful Quality Gates

Run the smallest meaningful set for your change first, then broaden when the surface area is larger.

```bash
composer run test:quick
composer run phpstan:strict
composer run format:check
composer run quality:docblocks
npm run test:js
```

Additional targeted gates:

```bash
composer run test:unit
composer run test:integration
composer run test:contract
npm run test:a11y:playwright:suite
npm run test:web-vitals
```

## Pull Request Expectations

- Fill out the pull request template.
- Explain the user-facing impact and the affected routes, commands, or workflows.
- Include the exact tests or checks you ran.
- Call out accessibility, privacy, security, billing, export, or business-access impact explicitly.
- Keep generated artifacts and dependency churn out of unrelated changes.

## Security Reports

Do not open public issues for suspected vulnerabilities. Follow [SECURITY.md](SECURITY.md) and email `security@paycal.app` with the details needed to reproduce and assess the report.
