# PayCal Security Policy

PayCal handles sensitive work-history, pay, account, business-access, and reporting data. Please report suspected vulnerabilities privately so they can be investigated without exposing users or businesses.

## Reporting a Vulnerability

Email `security@paycal.app` with:

- A concise description of the issue.
- Affected route, command, component, workflow, or file path.
- Reproduction steps, proof of concept, screenshots, or logs with secrets and personal data removed.
- Impact assessment, including whether worker records, business reports, authentication, billing, exports, Redis state, or encrypted envelopes may be affected.
- Your preferred contact information for follow-up.

Do not post vulnerability details in public GitHub issues, pull requests, discussions, social media, or public chat systems.

## Scope

In scope:

- Authentication, passkeys, recovery, session handling, and account lifecycle flows.
- Protected worker records, business visibility, consent, DEK/KEK wrapping, envelope reads, revocation, cache purge, and audit trails.
- Reports, exports, billing, Stripe webhook handling, and administrative surfaces.
- Browser JavaScript security, DOM sinks, CSP behavior, accessibility/security interactions, and local developer gates.
- CI/CD, repository metadata, dependency configuration, and deployment-support scripts.

Out of scope:

- Social engineering PayCal users, employees, or partners.
- Physical attacks, denial-of-service volume testing, spam, or automated scanning that degrades service.
- Reports that require access to accounts, systems, or data you do not own or have explicit permission to test.

## Safe Testing Rules

- Use your own accounts and local development data.
- Do not access, modify, export, screenshot, or retain another user's data.
- Do not attempt persistence, lateral movement, destructive actions, or production data extraction.
- Stop testing and report immediately if you encounter personal, business, payroll, credential, or secret material.

## Supported Versions

Security fixes are prioritized for the public `main` branch and the latest documented production release line. Older release lines may receive fixes when PayCal Technologies determines that a supported deployment or user workflow requires them.

## Response Targets

PayCal Technologies aims to acknowledge credible vulnerability reports within 3 business days and provide status updates as investigation proceeds. Remediation timing depends on severity, exploitability, affected systems, regression risk, and release coordination needs.

## Contact

- Security: `security@paycal.app`
- Product: https://paycal.app/
- Company: https://paycaltech.com/
- Policies: https://paycaltech.com/policies/
