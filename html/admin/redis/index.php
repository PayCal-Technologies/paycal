<?php declare(strict_types=1);

use PayCal\Domain\Authentication;
use PayCal\Domain\AdminSurface;
use PayCal\Domain\Render;

require_once '../../config.php';

$currentPage = 'PAGE_ADMIN';
$pageTitle = 'Redis Reliability Dashboard - [PayCal]';
$pageLabel = 'Redis Reliability Dashboard';

Authentication::redirectHomeIfUnauthenticated();
AdminSurface::redirectHomeIfPageUnavailable('/admin/redis/');

require_once HTML . '/header.php';

$cspNonceRaw = $_SERVER['CSP_NONCE'] ?? '';
$cspNonce = is_scalar($cspNonceRaw) ? (string) $cspNonceRaw : '';
echo '<link rel="stylesheet" href="' . htmlspecialchars(Render::cssURL('admin/redis'), ENT_QUOTES, 'UTF-8') . '" nonce="' . htmlspecialchars($cspNonce, ENT_QUOTES, 'UTF-8') . '">' . PHP_EOL;
?>
<section class="redis-admin" aria-label="Redis Tier-0 Reliability Dashboard">
  <header class="redis-admin__header panel w100 pad_md mar_sm">
    <div>
      <h1>Redis Tier-0 Reliability</h1>
      <p>Eviction safety, breaker state, mutation freeze, key churn and namespace quota visibility.</p>
    </div>
    <div class="redis-admin__actions">
      <button id="redis-refresh" class="btn btn_primary" type="button">Refresh</button>
    </div>
  </header>

  <section class="panel w100 pad_md mar_sm" aria-label="Control Plane">
    <h2>Control Plane</h2>
    <div class="redis-admin__controls">
      <label for="redis-control-reason">Reason</label>
      <input id="redis-control-reason" type="text" maxlength="140" placeholder="maintenance, failover drill, incident response">
      <div class="redis-admin__control-buttons">
        <button id="redis-freeze-on" class="btn btn_delete" type="button">Enable Freeze</button>
        <button id="redis-freeze-off" class="btn" type="button">Disable Freeze</button>
        <button id="redis-breaker-open" class="btn" type="button">Open Breaker</button>
        <button id="redis-breaker-reset" class="btn" type="button">Reset Breaker</button>
      </div>
      <p id="redis-control-status" class="status_message" aria-live="polite">Ready.</p>
    </div>
  </section>

  <section class="redis-admin__grid" aria-label="Reliability Snapshot">
    <article class="panel pad_md">
      <h3>Mutation State</h3>
      <dl>
        <dt>Freeze</dt><dd id="metric-freeze">-</dd>
        <dt>Breaker</dt><dd id="metric-breaker">-</dd>
        <dt>Failure Count</dt><dd id="metric-failures">-</dd>
        <dt>Success Count</dt><dd id="metric-successes">-</dd>
      </dl>
    </article>
    <article class="panel pad_md">
      <h3>Redis Runtime</h3>
      <dl>
        <dt>Used Memory</dt><dd id="metric-memory">-</dd>
        <dt>Max Memory</dt><dd id="metric-max-memory">-</dd>
        <dt>Memory %</dt><dd id="metric-memory-percent">-</dd>
        <dt>Evicted Keys</dt><dd id="metric-evicted">-</dd>
        <dt>Eviction Rate/min</dt><dd id="metric-eviction-rate">-</dd>
      </dl>
    </article>
    <article class="panel pad_md">
      <h3>Traffic and CPU</h3>
      <dl>
        <dt>Connected Clients</dt><dd id="metric-clients">-</dd>
        <dt>Ops/sec</dt><dd id="metric-ops">-</dd>
        <dt>CPU Sys</dt><dd id="metric-cpu-sys">-</dd>
        <dt>CPU User</dt><dd id="metric-cpu-user">-</dd>
      </dl>
    </article>
  </section>

  <section class="panel w100 pad_md mar_sm" aria-label="Tier-0 Quotas">
    <h2>Tier-0 Namespace Quotas</h2>
    <div id="redis-quota-table" class="redis-admin__table"></div>
  </section>

  <section class="panel w100 pad_md mar_sm" aria-label="Redis Alerts">
    <h2>Active Alerts</h2>
    <div id="redis-alerts-table" class="redis-admin__table"></div>
  </section>

  <section class="panel w100 pad_md mar_sm" aria-label="Namespace Churn">
    <h2>Namespace Counts and Churn</h2>
    <div id="redis-churn-table" class="redis-admin__table"></div>
  </section>
</section>
<?= Render::jsScript('admin/redis') ?>
<?php
require_once HTML . '/footer.php';
