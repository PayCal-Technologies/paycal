# Stripe Webhook Queue Monitoring & Setup Guide

## Overview

The Stripe webhook queue system implements an async, fault-tolerant pattern with three operational components:

1. **Queue Ingestion** (fast-ack webhook endpoint returns 200 immediately)
2. **Queue Draining** (background worker processes queued events)
3. **Queue Monitoring** (health checks, alerting, operational dashboards)

This guide covers setup, operational monitoring, and troubleshooting.

---

## Architecture

### Queue Flow

```
Stripe Event
    ↓
Webhook Endpoint (/api/v1/billing/webhook/)
    ↓
enqueueWebhook() → Validate Signature → JSON Envelope → Redis LPUSH → 200 OK (fast-ack)
    ↓
[Queue: billing:webhook:queue (max 2000)]
    ↓
drainWebhookQueue() (cron/manual) → process Event → Retry/Dead-Letter
    ↓
Success: telemetry recorded
Retryable Error: Re-enqueue (max 5 attempts)
Non-retryable Error: Dead-Letter (billing:webhook:dead_letter)
```

### Queue Bounds

- **Queue**: Max 2000 items (unprocessed events)
- **Dead-Letter**: Max 500 items (permanently failed events)
- **Retry Limit**: 5 attempts per event

---

## Operational Components

### 1. Queue Draining Script

Located at: `scripts/stripe-webhook-queue-drain.php`

**Usage:**

```bash
# Drain up to 50 events (default=25)
php scripts/stripe-webhook-queue-drain.php --max=50

# Get output as JSON (for monitoring integrations)
php scripts/stripe-webhook-queue-drain.php --max=100 --json

# Drain with verbose logging
php scripts/stripe-webhook-queue-drain.php --max=25
```

**Output Format (default):**

```
Stripe Webhook Queue Drain Report
====================================
Processed:     45
Succeeded:     40
Failed:        5
Requeued:      2
Dead-Lettered: 3
Queue Depth:   1200
```

**Output Format (--json):**

```json
{
  "processed": 45,
  "succeeded": 40,
  "failed": 5,
  "requeued": 2,
  "dead_lettered": 3,
  "queue_depth": 1200,
  "timestamp": "2026-04-03T07:30:15Z"
}
```

### 2. Health Monitoring Endpoint

**Admin-only endpoint:**

```
GET /api/v1/health/webhook-queue (requires admin auth)
```

**Response:**

```json
{
  "healthy": true,
  "queue_depth": 450,
  "queue_percent": 22.5,
  "dead_letter_depth": 35,
  "dead_letter_percent": 7.0,
  "alerts": [
    {
      "id": "queue:depth:warning",
      "code": "WEBHOOK_QUEUE_BACKLOG",
      "severity": "warning",
      "value": 450,
      "threshold": 500,
      "message": "Stripe webhook queue backlog: 450 unprocessed events."
    }
  ]
}
```

### 3. Alert Thresholds

Alerts are emitted to SecurityLog and throttled to prevent operator spam:

| Alert | Severity | Threshold | Throttle |
|-------|----------|-----------|----------|
| Queue Backlog | Warning | 500 items | 5 min |
| Queue Critical | Critical | 1000 items | 5 min |
| Dead-Letter Accumulating | Warning | 50 items | 5 min |
| Dead-Letter Critical | Critical | 200 items | 5 min |

---

## Setup Instructions

### Option A: Cron Job (Recommended for Production)

**1. Create cron entry on application server:**

```bash
# As www-data or application user
crontab -e

# Add every 5 minutes:
*/5 * * * * /usr/bin/php /var/www/paycal/dev/scripts/stripe-webhook-queue-drain.php --max=100 >> /var/log/paycal/webhook-queue.log 2>&1
```

**2. Monitor logs:**

```bash
# Watch drain activity
tail -f /var/log/paycal/webhook-queue.log

# Check for alerts
grep -i "error\|critical\|dead" /var/log/paycal/webhook-queue.log
```

### Option B: Systemd Timer (Alternative)

**1. Create service file `/etc/systemd/system/paycal-webhook-drain.service`:**

```ini
[Unit]
Description=PayCal Stripe Webhook Queue Drain
After=network.target

[Service]
Type=oneshot
User=www-data
WorkingDirectory=/var/www/paycal/dev
ExecStart=/usr/bin/php /var/www/paycal/dev/scripts/stripe-webhook-queue-drain.php --max=100
StandardOutput=journal
StandardError=journal
```

**2. Create timer file `/etc/systemd/system/paycal-webhook-drain.timer`:**

```ini
[Unit]
Description=PayCal Webhook Queue Drain Timer
Requires=paycal-webhook-drain.service

[Timer]
OnBootSec=5min
OnUnitActiveSec=5min
Persistent=true

[Install]
WantedBy=timers.target
```

**3. Enable and start:**

```bash
sudo systemctl daemon-reload
sudo systemctl enable paycal-webhook-drain.timer
sudo systemctl start paycal-webhook-drain.timer

# Check status
sudo systemctl status paycal-webhook-drain.timer
```

### Option C: Manual Monitoring (Development)

**1. Check queue health via admin dashboard:**

Navigate to: `https://dev.paycal.local/admin/health` (admin-only)

**2. Manual drain during troubleshooting:**

```bash
# Terminal session on app server
cd /var/www/paycal/dev
php scripts/stripe-webhook-queue-drain.php --max=500

# Repeat until queue is empty
while [ $(php scripts/stripe-webhook-queue-drain.php --json | jq .queue_depth) -gt 0 ]; do
  php scripts/stripe-webhook-queue-drain.php --max=100
  sleep 2
done
```

---

## Monitoring Integration

### SecurityLog Alerts

All alerts are logged to `security_log` table with code/severity for querying:

```sql
SELECT
  id, code, severity, message, timestamp
FROM security_log
WHERE code IN ('stripe_webhook_queue_alert', 'WEBHOOK_QUEUE_BACKLOG', 'WEBHOOK_DEAD_LETTER_ACCUMULATING')
ORDER BY timestamp DESC
LIMIT 100;
```

### Health Snapshot

Comprehensive health endpoint includes webhook queue metrics:

```bash
curl -H "Authorization: Bearer $ADMIN_TOKEN" \
  https://dev.paycal.local/api/v1/health/snapshot | jq .webhook_queue
```

### Metrics Service

Query queue health directly in code:

```php
use PayCal\Domain\StripeBillingQueueMonitor;

$health = StripeBillingQueueMonitor::getQueueHealth();

if (!$health['healthy']) {
  foreach ($health['alerts'] as $alert) {
    if ($alert['severity'] === 'critical') {
      notifyOncall($alert['message']);
    }
  }
}
```

---

## Troubleshooting

### Queue Backlog Growing (Not Draining)

**Symptom:** Queue depth increasing, drain script runs but no events processed

**Diagnosis:**

1. Check if cron job is running:
   ```bash
   ps aux | grep webhook-queue-drain.php
   ```

2. Check Redis connectivity:
   ```bash
   redis-cli LLEN billing:webhook:queue
   redis-cli LLEN billing:webhook:dead_letter
   ```

3. Check error logs:
   ```bash
   tail -f /var/log/paycal/webhook-queue.log
   tail -f /var/log/paycal/error.log | grep -i webhook
   ```

**Resolution:**

- Verify Redis is running and has available memory
- Check application permissions (www-data user can write to queue)
- Manually drain with debugging: `php scripts/stripe-webhook-queue-drain.php --max=10`

### Dead-Letter Growing

**Symptom:** Dead-letter buffer accumulating non-retryable failures

**Diagnosis:**

1. Query dead-letter events:
   ```bash
   redis-cli LRANGE billing:webhook:dead_letter 0 -1 | head -10
   ```

2. Inspect SecurityLog for failure patterns:
   ```sql
   SELECT * FROM security_log 
   WHERE code = 'stripe_webhook_queue_alert'
     AND severity = 'critical'
   ORDER BY timestamp DESC LIMIT 20;
   ```

**Resolution:**

- **Verification Failed**: Check webhook signature secret in .env matches Stripe dashboard
- **Invalid Data**: Contact Stripe support if receiving malformed events
- **Subscription Not Found**: Data sync issue; verify user subscriptions
- **Manual Retry**: Extract event from dead-letter, fix root cause, re-enqueue

### High Memory Usage

**Symptom:** Redis consuming excessive memory despite queue draining

**Diagnosis:**

1. Check Redis memory:
   ```bash
   redis-cli INFO memory | grep used_memory
   ```

2. Check queue size and retention:
   ```bash
   redis-cli LLEN billing:webhook:queue
   redis-cli LLEN billing:webhook:dead_letter
   ```

**Resolution:**

- Increase drain batch size temporarily: `--max=500`
- Check for memory leaks in event processing (telemetry, logging)
- Review Redis db size distribution: `redis-cli INFO keyspace`

---

## Alert Response Runbook

### Queue Backlog Warning (500+ unprocessed)

| Level | Action | Urgency |
|-------|--------|---------|
| Warning | Monitor drain rate, ensure cron running | Low |
| Critical (1000+) | Increase drain batch size, investigate performance | Medium |

**Quick Fix:**

```bash
# Temporarily increase drain rate
*/2 * * * * /usr/bin/php .../stripe-webhook-queue-drain.php --max=500
```

### Dead-Letter Critical (200+ failed)

| Level | Action | Urgency |
|-------|--------|---------|
| Warning | Review failure messages in logs | Medium |
| Critical | Investigate root cause, pause until fixed | High |

**Investigation:**

```bash
# Extract latest dead-lettered event
redis-cli LRANGE billing:webhook:dead_letter 0 0

# Decode JSON payload
echo '...' | jq .
```

---

## Configuration (Advanced)

### Adjusting Thresholds

Edit `html/src/Domain/StripeBillingQueueMonitor.php`:

```php
private static function policy(): array
{
  return [
    'queue_depth_warning' => 500,      // Adjust these values
    'queue_depth_critical' => 1000,
    'dead_letter_warning' => 50,
    'dead_letter_critical' => 200,
    // ... (max bounds not user-configurable; require code change)
  ];
}
```

### Adjusting Retry Behavior

Edit `html/src/Domain/StripeBillingService.php` constants (top of file):

```php
private const WEBHOOK_QUEUE_MAX_RETRIES = 5;        // Max attempts
private const WEBHOOK_QUEUE_MAX_ITEMS = 2000;       // Queue size
private const WEBHOOK_DEAD_LETTER_MAX_ITEMS = 500;  // Dead-letter size
```

---

## Performance Expectations

**Typical Drain Performance:**

- **Events/second** per drain worker: 10-25 (depends on subscription complexity)
- **Processing time** per event: 40-100ms (includes database lookups)
- **Throughput for 5-min cron** (--max=100): ~50-100 events drained

**Scaling:**

- For >5000 events/day, increase `--max` batch size to 200-500
- For >50000 events/day, consider multi-worker drain (requires queueing system upgrade)

---

## Checklist for Production Deployment

- [ ] Cron job installed and verified running
- [ ] Log file path exists and is writable
- [ ] SecurityLog table created (auto-migrated via schema)
- [ ] Admin dashboard accessible at /admin/health
- [ ] Webhook queue endpoint tested with Stripe test events
- [ ] Monitoring dashboards configured (if using external monitoring)
- [ ] On-call team briefed on alert response runbook
- [ ] Dead-letter inspection procedure documented
- [ ] Backup/recovery procedure tested
