<?php declare(strict_types=1);

/**
 * TraceGate diagnostic policy.
 *
 * Production: module-level production_allowed permits namespaced events for that
 * module when not explicitly listed under `events`. Explicit `events` entries
 * can override sink and security_log_event aliases.
 */

return [
  'version' => '1',

  'severity_order' => [
    'debug' => 10,
    'info' => 20,
    'warn' => 30,
    'error' => 40,
  ],

  'defaults' => [
    'enabled' => false,
    'severity_floor' => 'error',
    'sink' => 'none',
    'sample_rate' => 1.0,
  ],

  'capture_limits' => [
    'max_events_per_request' => 100,
    'max_context_keys' => 32,
    'max_string_length' => 256,
    'max_payload_bytes' => 8192,
  ],

  'presets' => [
    'auth_investigation' => [
      'label' => 'Auth Investigation',
      'modules' => ['auth', 'account', 'security', 'request_guard'],
    ],
    'business_rename_review' => [
      'label' => 'Business Rename Review',
      'modules' => ['business_sites', 'business_reports', 'request_guard', 'security'],
    ],
    'calendar_save_issue' => [
      'label' => 'Calendar Save Issue',
      'modules' => ['calendar_mutation', 'lock_boundary', 'payroll', 'ui_hydration'],
    ],
    'stripe_payment_issue' => [
      'label' => 'Stripe Payment Issue',
      'modules' => ['stripe', 'account', 'business_reports'],
    ],
    'cache_investigation' => [
      'label' => 'Cache Investigation',
      'modules' => ['redis', 'ui_hydration', 'reports'],
    ],
    'soc2_evidence_capture' => [
      'label' => 'SOC2 Evidence Capture',
      'modules' => ['soc2', 'chain_verification', 'security'],
    ],
  ],

  'package_groups' => [
    'core' => ['label' => 'Core'],
    'workspace' => ['label' => 'Calendar & Workspace'],
    'business' => ['label' => 'Business'],
    'infrastructure' => ['label' => 'Infrastructure'],
  ],

  'environments' => [
    'mac' => [
      'enabled' => true,
      'severity_floor' => 'debug',
      'sink' => 'file',
      'sample_rate' => 1.0,
    ],
    'dev' => [
      'enabled' => true,
      'severity_floor' => 'info',
      'sink' => 'file',
      'sample_rate' => 1.0,
    ],
    'prod' => [
      'enabled' => false,
      'severity_floor' => 'warn',
      'sink' => 'none',
      'sample_rate' => 1.0,
    ],
  ],

  'modules' => [
    'auth' => [
      'label' => 'Auth',
      'group' => 'core',
      'production_allowed' => true,
      'severity_floor' => 'info',
      'sink' => 'security',
    ],
    'request_guard' => [
      'label' => 'Request Guard',
      'group' => 'core',
      'production_allowed' => true,
      'severity_floor' => 'warn',
      'sink' => 'security',
    ],
    'security' => [
      'label' => 'Security Controls',
      'group' => 'core',
      'production_allowed' => true,
      'severity_floor' => 'warn',
      'sink' => 'security',
    ],
    'account' => [
      'label' => 'Account Actions',
      'group' => 'core',
      'production_allowed' => true,
      'severity_floor' => 'info',
      'sink' => 'security',
    ],
    'calendar_mutation' => [
      'label' => 'Calendar',
      'group' => 'workspace',
      'enabled' => false,
      'severity_floor' => 'error',
      'sink' => 'none',
    ],
    'sites' => [
      'label' => 'Sites',
      'group' => 'workspace',
      'enabled' => false,
      'severity_floor' => 'error',
      'sink' => 'none',
    ],
    'reports' => [
      'label' => 'Reports',
      'group' => 'workspace',
      'enabled' => false,
      'severity_floor' => 'error',
      'sink' => 'none',
    ],
    'payroll' => [
      'label' => 'Payroll Calculation',
      'group' => 'workspace',
      'enabled' => false,
      'severity_floor' => 'error',
      'sink' => 'none',
    ],
    'lock_boundary' => [
      'label' => 'Lock Boundary',
      'group' => 'workspace',
      'production_allowed' => true,
      'severity_floor' => 'warn',
      'sink' => 'security',
    ],
    'ui_hydration' => [
      'label' => 'UI Hydration',
      'group' => 'workspace',
      'enabled' => false,
      'severity_floor' => 'error',
      'sink' => 'none',
    ],
    'business_sites' => [
      'label' => 'Business Sites',
      'group' => 'business',
      'enabled' => false,
      'severity_floor' => 'error',
      'sink' => 'none',
    ],
    'business_reports' => [
      'label' => 'Business Reports',
      'group' => 'business',
      'enabled' => false,
      'severity_floor' => 'error',
      'sink' => 'none',
    ],
    'stripe' => [
      'label' => 'Stripe Webhooks',
      'group' => 'infrastructure',
      'production_allowed' => true,
      'severity_floor' => 'info',
      'sink' => 'security',
    ],
    'redis' => [
      'label' => 'Redis Cache / Reliability',
      'group' => 'infrastructure',
      'production_allowed' => true,
      'severity_floor' => 'error',
      'sink' => 'security',
    ],
    'soc2' => [
      'label' => 'SOC2 Evidence',
      'group' => 'infrastructure',
      'severity_floor' => 'info',
      'sink' => 'none',
    ],
    'chain_verification' => [
      'label' => 'Chain Verification',
      'group' => 'infrastructure',
      'severity_floor' => 'info',
      'sink' => 'none',
    ],
  ],

  'events' => [
    'request_guard.rate_limit_triggered' => [
      'module' => 'request_guard',
      'severity' => 'warn',
      'sink' => 'security',
      'production_allowed' => true,
      'security_log_event' => 'rate_limit_triggered',
    ],
    'lock_boundary.mutation_blocked' => [
      'module' => 'lock_boundary',
      'severity' => 'warn',
      'sink' => 'security',
      'production_allowed' => true,
      'security_log_event' => 'entry_locked_attempt',
    ],
    'redis.tier0_alert' => [
      'module' => 'redis',
      'severity' => 'error',
      'sink' => 'security',
      'production_allowed' => true,
      'security_log_event' => 'redis_tier0_alert',
    ],
    'stripe.webhook_verified' => [
      'module' => 'stripe',
      'severity' => 'info',
      'sink' => 'security',
      'production_allowed' => true,
      'security_log_event' => 'billing_webhook_processed',
    ],
    'stripe.webhook_failed' => [
      'module' => 'stripe',
      'severity' => 'warn',
      'sink' => 'security',
      'production_allowed' => true,
      'security_log_event' => 'billing_webhook_failed',
    ],
    'stripe.webhook_queue_alert' => [
      'module' => 'stripe',
      'severity' => 'warn',
      'sink' => 'security',
      'production_allowed' => true,
      'security_log_event' => 'stripe_webhook_queue_alert',
    ],
  ],

  'migration_status' => 'security_log_bridged',
  'migration_notes' => [
    'SecurityLog::log() routes mapped events through Argus; unmapped prod events are dropped.',
    'SecurityLog specialized helpers emit via Argus directly.',
    'StripeBillingService webhook outcomes emit stripe.webhook_verified/failed via Argus.',
    'SecurityLogSink calls SecurityLog::writeRecord() to avoid policy recursion.',
  ],
];
