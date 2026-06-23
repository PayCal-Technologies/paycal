<?php declare(strict_types=1);

use PayCal\Domain\AdminSurface;
use PayCal\Domain\Authentication;
use PayCal\Domain\FeedbackRepository;
use PayCal\Domain\InputSanitizer;
use PayCal\Domain\Render;
use PayCal\Domain\User;

require_once __DIR__ . '/../../config.php';

$currentPage = 'PAGE_ADMIN_FEEDBACK';
$pageTitle = 'Feedback - [PayCal]';
$pageLabel = 'Feedback';

Authentication::redirectHomeIfUnauthenticated();
AdminSurface::redirectHomeIfPageUnavailable('/admin/feedback/');

$flash = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && User::isAdmin()) {
  $feedbackId = InputSanitizer::postString('feedback_id');
  $status = InputSanitizer::postString('status');
  $adminNotes = InputSanitizer::postString('admin_notes');
  $duplicateOf = InputSanitizer::postString('duplicate_of');
  $flash = FeedbackRepository::updateAdminFields($feedbackId, $status, $adminNotes, $duplicateOf)
    ? 'Feedback updated.'
    : 'Unable to update feedback.';
}

$filters = [
  'status' => InputSanitizer::getString('status'),
  'severity' => InputSanitizer::getString('severity'),
  'category' => InputSanitizer::getString('category'),
  'page' => InputSanitizer::getString('page'),
  'role' => InputSanitizer::getString('role'),
  'date_range' => InputSanitizer::getString('date_range'),
];
$rows = FeedbackRepository::list($filters, 150);

require_once HTML . '/header.php';

$cspNonce = htmlspecialchars((string) ($_SERVER['CSP_NONCE'] ?? ''), ENT_QUOTES, 'UTF-8');
echo PHP_EOL . '<link rel="stylesheet" href="' . htmlspecialchars(Render::cssURL('admin'), ENT_QUOTES, 'UTF-8') . '" nonce="' . $cspNonce . '">' . PHP_EOL;

$h = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$selected = static fn(string $actual, string $expected): string => $actual === $expected ? ' selected' : '';

$categoryLabels = [
  'bug' => 'Bug',
  'confusing' => 'Confusing',
  'missing_feature' => 'Missing Feature',
  'ui_layout' => 'UI / Layout',
  'accessibility' => 'Accessibility',
  'payroll_calculation' => 'Payroll / Calculation',
  'calendar' => 'Calendar',
  'business' => 'Business',
  'privacy_trust' => 'Privacy / Trust',
  'performance' => 'Performance',
  'content_copy' => 'Content / Copy',
  'praise' => 'Praise',
];
?>
<section class="admin-feedback-page panel w100 pad_md" aria-labelledby="admin_feedback_title">
  <div class="admin-feedback-header">
    <div>
      <h1 id="admin_feedback_title">Echo Feedback</h1>
      <p class="text-muted">In-context Public Beta signals from the Signal Panel.</p>
    </div>
    <a class="btn btn_secondary" href="/admin/">Back to Admin</a>
  </div>

  <?php if ($flash !== '') { ?>
    <p class="status" role="status"><?= $h($flash) ?></p>
  <?php } ?>

  <form class="admin-feedback-filters" method="GET" action="/admin/feedback/">
    <label>Status
      <select name="status">
        <option value="">Any</option>
        <?php foreach (FeedbackRepository::statuses() as $statusOption) { ?>
          <option value="<?= $h($statusOption) ?>"<?= $selected((string) $filters['status'], $statusOption) ?>><?= $h($statusOption) ?></option>
        <?php } ?>
      </select>
    </label>
    <label>Severity
      <select name="severity">
        <option value="">Any</option>
        <?php foreach (['blocking', 'high', 'medium', 'low'] as $severityOption) { ?>
          <option value="<?= $h($severityOption) ?>"<?= $selected((string) $filters['severity'], $severityOption) ?>><?= $h($severityOption) ?></option>
        <?php } ?>
      </select>
    </label>
    <label>Category
      <select name="category">
        <option value="">Any</option>
        <?php foreach ($categoryLabels as $value => $label) { ?>
          <option value="<?= $h($value) ?>"<?= $selected((string) $filters['category'], $value) ?>><?= $h($label) ?></option>
        <?php } ?>
      </select>
    </label>
    <label>Page
      <input type="search" name="page" value="<?= $h((string) $filters['page']) ?>" placeholder="calendar, business, settings">
    </label>
    <label>Role
      <input type="search" name="role" value="<?= $h((string) $filters['role']) ?>" placeholder="owner, admin, user">
    </label>
    <label>Date range
      <select name="date_range">
        <option value="">Any</option>
        <option value="7"<?= $selected((string) $filters['date_range'], '7') ?>>Last 7 days</option>
        <option value="30"<?= $selected((string) $filters['date_range'], '30') ?>>Last 30 days</option>
        <option value="90"<?= $selected((string) $filters['date_range'], '90') ?>>Last 90 days</option>
      </select>
    </label>
    <button class="btn btn_primary" type="submit">Filter</button>
  </form>

  <div class="admin-feedback-table" role="region" aria-label="Feedback submissions" tabindex="0">
    <table>
      <thead>
        <tr>
          <th>Created</th>
          <th>Severity</th>
          <th>Category</th>
          <th>Topic</th>
          <th>Page</th>
          <th>User role</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php if ($rows === []) { ?>
        <tr><td colspan="8">No feedback found for these filters.</td></tr>
      <?php } ?>
      <?php foreach ($rows as $row) {
        $feedbackId = (string) ($row['feedback_id'] ?? '');
        $detailId = 'feedback_detail_' . preg_replace('/[^a-z0-9_]/', '_', $feedbackId);
        $category = (string) ($row['category'] ?? '');
      ?>
        <tr>
          <td><?= $h((string) ($row['created_at'] ?? '')) ?></td>
          <td><span class="admin-feedback-severity admin-feedback-severity-<?= $h((string) ($row['severity'] ?? '')) ?>"><?= $h((string) ($row['severity'] ?? '')) ?></span></td>
          <td><?= $h($categoryLabels[$category] ?? $category) ?></td>
          <td><?= $h((string) ($row['topic'] ?? '')) ?></td>
          <td><code><?= $h((string) ($row['page_path'] ?? '')) ?></code></td>
          <td><?= $h((string) ($row['user_role'] ?? '')) ?></td>
          <td><?= $h((string) ($row['status'] ?? '')) ?></td>
          <td><a class="btn btn_secondary" href="#<?= $h($detailId) ?>">View</a></td>
        </tr>
        <tr id="<?= $h($detailId) ?>" class="admin-feedback-detail">
          <td colspan="8">
            <div class="admin-feedback-detail-grid">
              <section>
                <h2><?= $h((string) ($row['topic'] ?? '')) ?></h2>
                <p><?= nl2br($h((string) ($row['notes'] ?? ''))) ?></p>
                <dl>
                  <dt>Feedback ID</dt><dd><code><?= $h($feedbackId) ?></code></dd>
                  <dt>User UUID</dt><dd><code><?= $h((string) ($row['user_uuid'] ?? '')) ?></code></dd>
                  <dt>Page title</dt><dd><?= $h((string) ($row['page_title'] ?? '')) ?></dd>
                  <dt>Tags</dt><dd><code><?= $h((string) ($row['tags_json'] ?? '[]')) ?></code></dd>
                  <dt>Pain points</dt><dd><code><?= $h((string) ($row['pain_points_json'] ?? '[]')) ?></code></dd>
                </dl>
              </section>
              <section>
                <h2>Triage</h2>
                <form method="POST" action="/admin/feedback/">
                  <input type="hidden" name="feedback_id" value="<?= $h($feedbackId) ?>">
                  <label>Status
                    <select name="status">
                      <?php foreach (FeedbackRepository::statuses() as $statusOption) { ?>
                        <option value="<?= $h($statusOption) ?>"<?= $selected((string) ($row['status'] ?? ''), $statusOption) ?>><?= $h($statusOption) ?></option>
                      <?php } ?>
                    </select>
                  </label>
                  <label>Duplicate of
                    <input type="text" name="duplicate_of" value="<?= $h((string) ($row['duplicate_of'] ?? '')) ?>" placeholder="fb_...">
                  </label>
                  <label>Admin notes
                    <textarea name="admin_notes" rows="5" maxlength="4000"><?= $h((string) ($row['admin_notes'] ?? '')) ?></textarea>
                  </label>
                  <button class="btn btn_primary" type="submit">Save Triage</button>
                </form>
              </section>
              <section class="admin-feedback-json">
                <h2>Context</h2>
                <pre><?= $h((string) ($row['context_json'] ?? '{}')) ?></pre>
                <h2>Diagnostics</h2>
                <pre><?= $h((string) ($row['diagnostics_json'] ?? '{}')) ?></pre>
              </section>
            </div>
          </td>
        </tr>
      <?php } ?>
      </tbody>
    </table>
  </div>
</section>
<?php
require_once HTML . '/footer.php';
