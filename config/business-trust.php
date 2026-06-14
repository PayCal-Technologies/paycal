<?php declare(strict_types=1);

/**
 * Business Trust & Visibility policy constants.
 */

$appEnv = strtolower(trim((string) (getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'production'))));

$publicRenameCooldownSeconds = match (true) {
  in_array($appEnv, ['mac', 'local'], true) => 5,
  in_array($appEnv, ['dev', 'staging'], true) => 300,
  default => 86400,
};

$trustedRenameCooldownSeconds = match (true) {
  in_array($appEnv, ['mac', 'local'], true) => 5,
  in_array($appEnv, ['dev', 'staging'], true) => 300,
  default => 21600,
};

return [
  'name' => [
    'min_length' => 2,
    'max_length' => 80,
    'auto_approve_max_score' => 20,
    'needs_review_max_score' => 59,
    'reject_public_max_score' => 89,
  ],
  'reserved_names' => [
    'paycal',
    'paycal support',
    'paycal admin',
    'paycal security',
    'administrator',
    'admin',
    'system',
    'support',
    'payroll canada',
    'cra',
    'government of canada',
    'stripe',
    'bank',
    'verified',
    'official',
  ],
  'risk_terms' => [
    'scam' => 40,
    'spam' => 40,
    'porn' => 40,
    'sex' => 40,
    'fuck' => 70,
    'shit' => 70,
  ],
  'business_public_rename_cooldown_seconds' => $publicRenameCooldownSeconds,
  'business_public_rename_cooldown_seconds_trusted' => $trustedRenameCooldownSeconds,
  'rename_cooldown_seconds_new_owner' => $publicRenameCooldownSeconds,
  'rename_cooldown_seconds_trusted_owner' => $trustedRenameCooldownSeconds,
  'rename_limit_new_owner_per_day' => 1,
  'rename_limit_trusted_owner_per_day' => 1,
];
