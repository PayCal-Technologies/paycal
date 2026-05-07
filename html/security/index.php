<?php declare(strict_types=1);

require_once __DIR__ . '/../config.php';

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = 'Security - [PayCal]';
$pageLabel = 'Security';

$repoRoot = dirname(__DIR__, 2);
$publicDir = $repoRoot . '/storage/soc2/public';

$summary = readPublicJson($publicDir . '/summary.json');
$controls = readPublicJson($publicDir . '/controls.json');
$narrative = readPublicJson($publicDir . '/narrative.json');

$controlRows = is_array($controls['controls'] ?? null) ? $controls['controls'] : [];
$narrativeRows = is_array($narrative['narrative'] ?? null) ? $narrative['narrative'] : [];
$summaryCounts = (array) ($summary['control_summary'] ?? []);

require_once HTML . '/header.php';
?>
<article class="article doc-article">
  <header class="doc-article-header">
    <h1>Security Trust Hub</h1>
    <p class="deck">PayCal is aligned with SOC 2 security principles and operates a deterministic control evidence pipeline.</p>
  </header>

  <section class="doc-article-body">
    <section class="doc-section highlight">
      <h2>Overview</h2>
      <p>Security controls are continuously evaluated from evidence contracts, deterministic validation, and monthly lifecycle snapshots.</p>
      <ul class="doc-fact-list">
        <li><strong>Posture:</strong> <?php echo htmlspecialchars((string) ($summary['label'] ?? 'Aligned with SOC 2 security principles'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><strong>Program State:</strong> SOC 2 readiness in progress</li>
        <li><strong>Global Score:</strong> <?php echo htmlspecialchars((string) ($summary['global_score'] ?? '0'), ENT_QUOTES, 'UTF-8'); ?></li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Controls</h2>
      <ul class="doc-fact-list">
        <li><strong>PASS:</strong> <?php echo (int) ($summaryCounts['PASS'] ?? 0); ?></li>
        <li><strong>WARN:</strong> <?php echo (int) ($summaryCounts['WARN'] ?? 0); ?></li>
        <li><strong>FAIL:</strong> <?php echo (int) ($summaryCounts['FAIL'] ?? 0); ?></li>
      </ul>
      <?php if ($controlRows !== []): ?>
      <div class="soc2-summary-card">
        <table class="table table--audit">
          <thead>
            <tr>
              <th>Control</th>
              <th>Status</th>
              <th>System Component</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($controlRows as $row): ?>
              <?php if (!is_array($row)) { continue; } ?>
              <tr>
                <td><?php echo htmlspecialchars((string) ($row['control'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars((string) ($row['status'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars((string) ($row['system_component'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </section>

    <section class="doc-section">
      <h2>Practices</h2>
      <ul class="doc-fact-list">
        <li>RBAC and access telemetry</li>
        <li>Encryption and key lifecycle controls</li>
        <li>Backup, disaster recovery, and operational readiness checks</li>
        <li>Change trace and approval governance</li>
      </ul>
    </section>

    <section class="doc-section success">
      <h2>Last Updated</h2>
      <p><?php echo htmlspecialchars((string) ($summary['last_updated'] ?? gmdate('c')), ENT_QUOTES, 'UTF-8'); ?></p>
      <p>This page reflects sanitized trust-bundle outputs only; internal paths and raw logs are excluded.</p>
    </section>

    <section class="doc-section highlight">
      <h2>Contact</h2>
      <p>Security inquiries: <a class="doc-read-more" href="mailto:security@paycal.app">security@paycal.app</a></p>
      <p>SOC 2 NDA report request: <a class="doc-read-more" href="/soc2/request/">/soc2/request/</a></p>
    </section>

    <?php if ($narrativeRows !== []): ?>
    <section class="doc-section">
      <h2>Narrative Snapshot</h2>
      <ul class="doc-fact-list">
        <?php foreach ($narrativeRows as $row): ?>
          <?php if (!is_array($row)) { continue; } ?>
          <li><strong><?php echo htmlspecialchars((string) ($row['control'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>:</strong> <?php echo htmlspecialchars((string) ($row['narrative'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></li>
        <?php endforeach; ?>
      </ul>
    </section>
    <?php endif; ?>
  </section>
</article>
<?php
require_once HTML . '/footer.php';

/** @return array<string, mixed> */
function readPublicJson(string $path): array
{
  $raw = @file_get_contents($path);
  $decoded = json_decode((string) $raw, true);
  return is_array($decoded) ? $decoded : [];
}
