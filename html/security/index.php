<?php declare(strict_types=1);

require_once __DIR__ . '/../config.php';

$currentPage = 'PAGE_TRANSPARENCY';

$i18nKeys = [
  'SECURITY_CONTACT_H2',
  'SECURITY_CONTACT_SECURITY_INQUIRIES',
  'SECURITY_CONTROLS_H2',
  'SECURITY_CONTROL_FAIL',
  'SECURITY_CONTROL_PASS',
  'SECURITY_CONTROL_WARN',
  'SECURITY_GLOBAL_SCORE_LABEL',
  'SECURITY_LAST_UPDATED_H2',
  'SECURITY_LAST_UPDATED_NOTE',
  'SECURITY_NARRATIVE_H2',
  'SECURITY_OVERVIEW_H2',
  'SECURITY_OVERVIEW_BODY',
  'SECURITY_PAGE_LABEL',
  'SECURITY_PAGE_TITLE',
  'SECURITY_POSTURE_FALLBACK',
  'SECURITY_POSTURE_LABEL',
  'SECURITY_PRACTICES_H2',
  'SECURITY_PRACTICE_ACCESS_TELEMETRY',
  'SECURITY_PRACTICE_BACKUP_RECOVERY',
  'SECURITY_PRACTICE_CHANGE_GOVERNANCE',
  'SECURITY_PRACTICE_ENCRYPTION_KEYS',
  'SECURITY_PROGRAM_STATE_LABEL',
  'SECURITY_PROGRAM_STATE_VALUE',
  'SECURITY_SOC2_NDA_REPORT_REQUEST',
  'SECURITY_TABLE_CONTROL',
  'SECURITY_TABLE_STATUS',
  'SECURITY_TABLE_SYSTEM_COMPONENT',
  'SECURITY_TRUST_HUB_DECK',
  'SECURITY_TRUST_HUB_H1',
];
$i18n = [];
foreach ($i18nKeys as $i18nKey) {
  $i18n[$i18nKey] = \PayCal\Domain\Strings::i18n($i18nKey);
}

$pageTitle = $i18n['SECURITY_PAGE_TITLE'];
$pageLabel = $i18n['SECURITY_PAGE_LABEL'];

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
    <h1><?php echo htmlspecialchars($i18n['SECURITY_TRUST_HUB_H1'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="deck"><?php echo htmlspecialchars($i18n['SECURITY_TRUST_HUB_DECK'], ENT_QUOTES, 'UTF-8'); ?></p>
  </header>

  <section class="doc-article-body">
    <section class="doc-section highlight">
      <h2><?php echo htmlspecialchars($i18n['SECURITY_OVERVIEW_H2'], ENT_QUOTES, 'UTF-8'); ?></h2>
      <p><?php echo htmlspecialchars($i18n['SECURITY_OVERVIEW_BODY'], ENT_QUOTES, 'UTF-8'); ?></p>
      <ul class="doc-fact-list">
        <li><strong><?php echo htmlspecialchars($i18n['SECURITY_POSTURE_LABEL'], ENT_QUOTES, 'UTF-8'); ?>:</strong> <?php echo htmlspecialchars((string) ($summary['label'] ?? $i18n['SECURITY_POSTURE_FALLBACK']), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><strong><?php echo htmlspecialchars($i18n['SECURITY_PROGRAM_STATE_LABEL'], ENT_QUOTES, 'UTF-8'); ?>:</strong> <?php echo htmlspecialchars($i18n['SECURITY_PROGRAM_STATE_VALUE'], ENT_QUOTES, 'UTF-8'); ?></li>
        <li><strong><?php echo htmlspecialchars($i18n['SECURITY_GLOBAL_SCORE_LABEL'], ENT_QUOTES, 'UTF-8'); ?>:</strong> <?php echo htmlspecialchars((string) ($summary['global_score'] ?? '0'), ENT_QUOTES, 'UTF-8'); ?></li>
      </ul>
    </section>

    <section class="doc-section">
      <h2><?php echo htmlspecialchars($i18n['SECURITY_CONTROLS_H2'], ENT_QUOTES, 'UTF-8'); ?></h2>
      <ul class="doc-fact-list">
        <li><strong><?php echo htmlspecialchars($i18n['SECURITY_CONTROL_PASS'], ENT_QUOTES, 'UTF-8'); ?>:</strong> <?php echo (int) ($summaryCounts['PASS'] ?? 0); ?></li>
        <li><strong><?php echo htmlspecialchars($i18n['SECURITY_CONTROL_WARN'], ENT_QUOTES, 'UTF-8'); ?>:</strong> <?php echo (int) ($summaryCounts['WARN'] ?? 0); ?></li>
        <li><strong><?php echo htmlspecialchars($i18n['SECURITY_CONTROL_FAIL'], ENT_QUOTES, 'UTF-8'); ?>:</strong> <?php echo (int) ($summaryCounts['FAIL'] ?? 0); ?></li>
      </ul>
      <?php if ($controlRows !== []): ?>
      <div class="soc2-summary-card">
        <table class="table table--audit">
          <thead>
            <tr>
              <th><?php echo htmlspecialchars($i18n['SECURITY_TABLE_CONTROL'], ENT_QUOTES, 'UTF-8'); ?></th>
              <th><?php echo htmlspecialchars($i18n['SECURITY_TABLE_STATUS'], ENT_QUOTES, 'UTF-8'); ?></th>
              <th><?php echo htmlspecialchars($i18n['SECURITY_TABLE_SYSTEM_COMPONENT'], ENT_QUOTES, 'UTF-8'); ?></th>
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
      <h2><?php echo htmlspecialchars($i18n['SECURITY_PRACTICES_H2'], ENT_QUOTES, 'UTF-8'); ?></h2>
      <ul class="doc-fact-list">
        <li><?php echo htmlspecialchars($i18n['SECURITY_PRACTICE_ACCESS_TELEMETRY'], ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars($i18n['SECURITY_PRACTICE_ENCRYPTION_KEYS'], ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars($i18n['SECURITY_PRACTICE_BACKUP_RECOVERY'], ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars($i18n['SECURITY_PRACTICE_CHANGE_GOVERNANCE'], ENT_QUOTES, 'UTF-8'); ?></li>
      </ul>
    </section>

    <section class="doc-section success">
      <h2><?php echo htmlspecialchars($i18n['SECURITY_LAST_UPDATED_H2'], ENT_QUOTES, 'UTF-8'); ?></h2>
      <p><?php echo htmlspecialchars((string) ($summary['last_updated'] ?? gmdate('c')), ENT_QUOTES, 'UTF-8'); ?></p>
      <p><?php echo htmlspecialchars($i18n['SECURITY_LAST_UPDATED_NOTE'], ENT_QUOTES, 'UTF-8'); ?></p>
    </section>

    <section class="doc-section highlight">
      <h2><?php echo htmlspecialchars($i18n['SECURITY_CONTACT_H2'], ENT_QUOTES, 'UTF-8'); ?></h2>
      <p><?php echo htmlspecialchars($i18n['SECURITY_CONTACT_SECURITY_INQUIRIES'], ENT_QUOTES, 'UTF-8'); ?>: <a class="doc-read-more" href="mailto:security@paycal.app">security@paycal.app</a></p>
      <p><?php echo htmlspecialchars($i18n['SECURITY_SOC2_NDA_REPORT_REQUEST'], ENT_QUOTES, 'UTF-8'); ?>: <a class="doc-read-more" href="/soc2/request/">/soc2/request/</a></p>
    </section>

    <?php if ($narrativeRows !== []): ?>
    <section class="doc-section">
      <h2><?php echo htmlspecialchars($i18n['SECURITY_NARRATIVE_H2'], ENT_QUOTES, 'UTF-8'); ?></h2>
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
