# PayCal Extension Overrides

Place private override extensions in this directory.

## Why overrides exist

- Keep PayCal Core stable and upgradeable.
- Allow private/canonical product differentiation without Core forks.
- Let self-hosted operators choose alternate extension packages.

Canonical paycal.app can ship private override variants while public/basic
packages remain available in-repo for third-party adopters.

## Discovery model

- Each override is a folder at `html/extensions/overrides/<extension-id>/`.
- Each override folder must provide `manifest.php`.
- Optional `bootstrap` file path is configured inside the manifest.

## Manifest contract

Example:

```php
<?php declare(strict_types=1);

return [
  'id' => 'organization-signals',
  'name' => 'Organization Signals (Private Override)',
  'version' => '2.0.0-private.1',
  'description' => 'Private UX and workflow enhancements.',
  'author' => 'PayCal Private',
  'license' => 'Proprietary',
  'core_compat' => '>=1.0.0',
  'enabled' => true,
  'capabilities' => [
    'organization.signal.owner_inbox' => 'advanced',
    'organization.signal.owner_triage' => true,
  ],
  'hooks' => [
    'organization.audit_event',
  ],
  'bootstrap' => 'bootstrap.php',
];
```

## Precedence rule

If an override exists for a given extension id, the basic extension with the same id is never loaded.

## Authoring checklist

1. Keep `id` exactly aligned with the basic package id you intend to replace.
2. Provide internal comments in bootstrap/hook files explaining operational role.
3. Keep payload handling defensive (`is_array`, `is_scalar`, safe defaults).
4. Log failures from risky code paths and avoid throwing uncaught exceptions.
5. Update package-local README when behavior or activation changes.

## Operational expectations

- Override packages should be owned by a clear internal team.
- Package README files should define activation, rollback, and support notes.
- Manifest `version` should advance for behavior changes to aid diagnostics.
