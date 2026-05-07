#!/usr/bin/env php
<?php declare(strict_types=1);

/**
 * Stripe Webhook Queue Drainer
 *
 * Purpose:
 * - Drain asynchronously queued Stripe webhook deliveries in controlled batches.
 * - Provide an operational entry point for cron/manual recovery during incidents.
 *
 * Why this script exists here:
 * - Stripe webhook endpoint now fast-ACKs by enqueueing payloads for async processing.
 * - Operations needs a deterministic tool to process backlog and inspect outcomes.
 *
 * Usage:
 *   php scripts/stripe-webhook-queue-drain.php [--max=25] [--json]
 */

require_once __DIR__ . '/../html/config.php';

use PayCal\Domain\StripeBillingService;

$max = 25;
$jsonOutput = false;

foreach ($argv as $arg) {
  if (str_starts_with($arg, '--max=')) {
    $max = max(1, min(25, (int) substr($arg, 6)));
  }

  if ($arg === '--json') {
    $jsonOutput = true;
  }
}

$service = new StripeBillingService();
$result = $service->drainWebhookQueue($max);

if ($jsonOutput) {
  echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
  exit($result['success'] ? 0 : 1);
}

$data = is_array($result['data'] ?? null) ? $result['data'] : [];

echo 'Stripe webhook queue drain' . PHP_EOL;
echo 'message=' . ($result['message'] ?? 'unknown') . PHP_EOL;
echo 'processed=' . (int) ($data['processed'] ?? 0) . PHP_EOL;
echo 'succeeded=' . (int) ($data['succeeded'] ?? 0) . PHP_EOL;
echo 'failed=' . (int) ($data['failed'] ?? 0) . PHP_EOL;
echo 'requeued=' . (int) ($data['requeued'] ?? 0) . PHP_EOL;
echo 'dead_lettered=' . (int) ($data['dead_lettered'] ?? 0) . PHP_EOL;
echo 'remaining=' . (int) ($data['remaining'] ?? 0) . PHP_EOL;

exit($result['success'] ? 0 : 1);
