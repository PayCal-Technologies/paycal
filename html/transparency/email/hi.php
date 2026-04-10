<?php
/**
 * Public Transparency: Email Architecture
 *
 * PURPOSE: Explain what transactional emails PayCal sends, how they are sent,
 * and how delivery and reliability are verified.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

$i18n = [];
$i18nKeys = [
  'BREADCRUMB',
  'HELP_TOC_TRANSPARENCY_HUB',
  'TRANSPARENCY_EMAIL_PAGE_TITLE',
  'TRANSPARENCY_EMAIL_DECK',
  'TRANSPARENCY_EMAIL_PRINCIPLES_TITLE',
  'TRANSPARENCY_EMAIL_PRINCIPLES_TEXT_1',
  'TRANSPARENCY_EMAIL_PRINCIPLES_FACT_TEMPLATE_PATHS',
  'TRANSPARENCY_EMAIL_WHAT_WE_SEND_TITLE',
  'TRANSPARENCY_EMAIL_DELIVERY_PIPELINE_TITLE',
  'TRANSPARENCY_EMAIL_SUPPORT_TELEMETRY_TITLE',
  'TRANSPARENCY_EMAIL_RELIABILITY_CHECKS_TITLE',
  'TRANSPARENCY_EMAIL_SCOPE_BOUNDARY_TITLE',
  'TRANSPARENCY_EMAIL_TABLE_ARIA',
  'TRANSPARENCY_EMAIL_TABLE_FLOW',
  'TRANSPARENCY_EMAIL_TABLE_TEMPLATE_FAMILY',
  'TRANSPARENCY_EMAIL_TABLE_PURPOSE',
  'TRANSPARENCY_EMAIL_FLOW_VERIFICATION',
  'TRANSPARENCY_EMAIL_FLOW_RECOVERY_EMAIL_VERIFICATION',
  'TRANSPARENCY_EMAIL_FLOW_RECOVERY_KEY_DELIVERY',
  'TRANSPARENCY_EMAIL_PRINCIPLES_ITEM_2',
  'TRANSPARENCY_EMAIL_PRINCIPLES_ITEM_3',
  'TRANSPARENCY_EMAIL_FLOW_VERIFICATION_PURPOSE',
  'TRANSPARENCY_EMAIL_FLOW_RECOVERY_EMAIL_PURPOSE',
  'TRANSPARENCY_EMAIL_FLOW_RECOVERY_KEY_PURPOSE',
  'TRANSPARENCY_EMAIL_FLOW_ACCOUNT_RECOVERY',
  'TRANSPARENCY_EMAIL_FLOW_ACCOUNT_RECOVERY_PURPOSE',
  'TRANSPARENCY_EMAIL_FLOW_EMAIL_CHANGE',
  'TRANSPARENCY_EMAIL_FLOW_EMAIL_CHANGE_PURPOSE',
  'TRANSPARENCY_EMAIL_FLOW_CONTACT_RELAY',
  'TRANSPARENCY_EMAIL_FLOW_CONTACT_RELAY_PURPOSE',
  'TRANSPARENCY_EMAIL_DELIVERY_TEXT_1',
  'TRANSPARENCY_EMAIL_DELIVERY_TEXT_3',
  'TRANSPARENCY_EMAIL_TELEMETRY_TEXT_1',
  'TRANSPARENCY_EMAIL_TELEMETRY_ITEM_3',
  'TRANSPARENCY_EMAIL_SCOPE_TEXT_1',
  'TRANSPARENCY_EMAIL_LAST_UPDATED_LABEL',
];
foreach ($i18nKeys as $key) {
  $i18n[$key] = \PayCal\Domain\Strings::i18n($key);
}

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = $i18n['TRANSPARENCY_EMAIL_PAGE_TITLE'] . ' - [PayCal]';
$pageLabel = $i18n['TRANSPARENCY_EMAIL_PAGE_TITLE'];
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current"><?php echo $i18n['TRANSPARENCY_EMAIL_PAGE_TITLE']; ?></span>
  </nav>

  <header class="doc-article-header">
    <h1><?php echo $i18n['TRANSPARENCY_EMAIL_PAGE_TITLE']; ?></h1>
    <p class="deck"><?php echo $i18n['TRANSPARENCY_EMAIL_DECK']; ?></p>
<p class="doc-article-meta">Published: <time datetime="2026-03-21">2026-03-21</time></p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2><?php echo $i18n['TRANSPARENCY_EMAIL_PRINCIPLES_TITLE']; ?></h2>
      <p><?php echo $i18n['TRANSPARENCY_EMAIL_PRINCIPLES_TEXT_1']; ?></p>
      <ul class="doc-fact-list">
        <li><?php echo $i18n['TRANSPARENCY_EMAIL_PRINCIPLES_FACT_TEMPLATE_PATHS']; ?></li>
        <li><?php echo $i18n['TRANSPARENCY_EMAIL_PRINCIPLES_ITEM_2']; ?></li>
        <li><?php echo $i18n['TRANSPARENCY_EMAIL_PRINCIPLES_ITEM_3']; ?></li>
      </ul>
    </section>

    <section class="doc-section">
      <h2><?php echo $i18n['TRANSPARENCY_EMAIL_WHAT_WE_SEND_TITLE']; ?></h2>
      <table class="doc-table" aria-label="<?php echo $i18n['TRANSPARENCY_EMAIL_TABLE_ARIA']; ?>">
        <thead>
          <tr>
            <th><?php echo $i18n['TRANSPARENCY_EMAIL_TABLE_FLOW']; ?></th>
            <th><?php echo $i18n['TRANSPARENCY_EMAIL_TABLE_PURPOSE']; ?></th>
            <th><?php echo $i18n['TRANSPARENCY_EMAIL_TABLE_TEMPLATE_FAMILY']; ?></th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><?php echo $i18n['TRANSPARENCY_EMAIL_FLOW_VERIFICATION']; ?></td>
            <td><?php echo $i18n['TRANSPARENCY_EMAIL_FLOW_VERIFICATION_PURPOSE']; ?></td>
            <td><code>email-verification-*</code></td>
          </tr>
          <tr>
            <td><?php echo $i18n['TRANSPARENCY_EMAIL_FLOW_RECOVERY_EMAIL_VERIFICATION']; ?></td>
            <td><?php echo $i18n['TRANSPARENCY_EMAIL_FLOW_RECOVERY_EMAIL_PURPOSE']; ?></td>
            <td><code>email-recovery-email-code-*</code></td>
          </tr>
          <tr>
            <td><?php echo $i18n['TRANSPARENCY_EMAIL_FLOW_RECOVERY_KEY_DELIVERY']; ?></td>
            <td><?php echo $i18n['TRANSPARENCY_EMAIL_FLOW_RECOVERY_KEY_PURPOSE']; ?></td>
            <td><code>email-recovery-key-*</code></td>
          </tr>
          <tr>
            <td><?php echo $i18n['TRANSPARENCY_EMAIL_FLOW_ACCOUNT_RECOVERY']; ?></td>
            <td><?php echo $i18n['TRANSPARENCY_EMAIL_FLOW_ACCOUNT_RECOVERY_PURPOSE']; ?></td>
            <td><code>email-account-recovery-code-*</code></td>
          </tr>
          <tr>
            <td><?php echo $i18n['TRANSPARENCY_EMAIL_FLOW_EMAIL_CHANGE']; ?></td>
            <td><?php echo $i18n['TRANSPARENCY_EMAIL_FLOW_EMAIL_CHANGE_PURPOSE']; ?></td>
            <td><code>email-change-code-*</code> and <code>email-change-confirmation-*</code></td>
          </tr>
          <tr>
            <td><?php echo $i18n['TRANSPARENCY_EMAIL_FLOW_CONTACT_RELAY']; ?></td>
            <td><?php echo $i18n['TRANSPARENCY_EMAIL_FLOW_CONTACT_RELAY_PURPOSE']; ?></td>
            <td><code>contact-email-*</code></td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section">
      <h2><?php echo $i18n['TRANSPARENCY_EMAIL_DELIVERY_PIPELINE_TITLE']; ?></h2>
      <p><?php echo $i18n['TRANSPARENCY_EMAIL_DELIVERY_TEXT_1']; ?></p>
      <ul class="doc-fact-list">
        <li><code>PayCal\Domain\EmailGarum</code>: workflow-level orchestration and template selection.</li>
        <li><code>PayCal\Domain\EmailTransport</code>: SMTP protocol transport (connect, STARTTLS, AUTH, send, close).</li>
      </ul>
      <p>Template rendering is performed via <code>PayCal\Domain\Render::template()</code>, with both HTML and text bodies built for each flow.</p>
      <p><?php echo $i18n['TRANSPARENCY_EMAIL_DELIVERY_TEXT_3']; ?></p>
    </section>

    <section class="doc-section">
      <h2><?php echo $i18n['TRANSPARENCY_EMAIL_SUPPORT_TELEMETRY_TITLE']; ?></h2>
      <p><?php echo $i18n['TRANSPARENCY_EMAIL_TELEMETRY_TEXT_1']; ?></p>
      <ul class="doc-fact-list">
        <li>Aggregate counters are recorded under <code>telemetry:contact:*</code> keys.</li>
        <li>JSONL event records are appended to rotated logs via <code>PayCal\Domain\ContactSupportTelemetry</code>.</li>
        <li><?php echo $i18n['TRANSPARENCY_EMAIL_TELEMETRY_ITEM_3']; ?></li>
      </ul>
    </section>

    <section class="doc-section success">
      <h2><?php echo $i18n['TRANSPARENCY_EMAIL_RELIABILITY_CHECKS_TITLE']; ?></h2>
      <ul class="doc-fact-list">
        <li><code>html/tests/Unit/EmailTemplateRenderTest.php</code> verifies all supported templates render with expected placeholder substitution.</li>
        <li><code>html/tests/Integration/LiveEmailTemplateSweepTest.php</code> provides opt-in live SMTP end-to-end template coverage.</li>
        <li><code>html/tests/Integration/EmailSendTest.php</code> provides opt-in single-message verification for SMTP, DKIM, DMARC, and Message-ID health.</li>
      </ul>
      <pre class="doc-code"># Opt-in live template sweep
cd html
PAYCAL_RUN_LIVE_EMAIL_SWEEP=1 PAYCAL_LIVE_EMAIL_RECIPIENT=you@example.com \
  ./vendor/bin/phpunit --configuration phpunit.xml tests/Integration/LiveEmailTemplateSweepTest.php

# Opt-in single email stack verification
cd html
PAYCAL_RUN_LIVE_EMAIL=1 PAYCAL_LIVE_EMAIL_RECIPIENT=you@example.com \
  ./vendor/bin/phpunit --configuration phpunit.xml tests/Integration/EmailSendTest.php</pre>
    </section>

    <section class="doc-section highlight">
      <h2><?php echo $i18n['TRANSPARENCY_EMAIL_SCOPE_BOUNDARY_TITLE']; ?></h2>
      <p><?php echo $i18n['TRANSPARENCY_EMAIL_SCOPE_TEXT_1']; ?></p>
      <p><strong><?php echo $i18n['TRANSPARENCY_EMAIL_LAST_UPDATED_LABEL']; ?></strong> March 21, 2026.</p>
    </section>
  </div>
</article>
<?php
require_once HTML.'/footer.php';
