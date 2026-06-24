<?php declare(strict_types=1);

use PayCal\Domain\AdminSurface;
use PayCal\Domain\Authentication;
use PayCal\Domain\Business\BusinessModerationService;
use PayCal\Domain\Business\BusinessSearchIndex;
use PayCal\Domain\Config\Environment;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;
use PayCal\Domain\InputSanitizer;
use PayCal\Domain\Render;
use PayCal\Domain\Strings;

require_once '../../config.php';

/**
 * @param array<string, string> $labels
 */
function businessModerationBadge(string $value, string $kind = 'generic'): string
{
  $normalized = strtolower(trim($value));
  if ($normalized === '') {
    return '<span class="business-moderation-badge business-moderation-badge--muted">—</span>';
  }

  $class = 'business-moderation-badge--muted';
  if ($kind === 'paid') {
    $class = match ($normalized) {
      'active', 'trialing' => 'business-moderation-badge--success',
      'past_due', 'disputed' => 'business-moderation-badge--warn',
      'canceled', 'cancelled' => 'business-moderation-badge--danger',
      default => 'business-moderation-badge--muted',
    };
  } elseif ($kind === 'visibility') {
    $class = match ($normalized) {
      'listed' => 'business-moderation-badge--success',
      'suspended', 'hidden' => 'business-moderation-badge--danger',
      default => 'business-moderation-badge--muted',
    };
  } elseif ($kind === 'moderation') {
    $class = match ($normalized) {
      'approved', 'not_required' => 'business-moderation-badge--success',
      'pending', 'needs_review' => 'business-moderation-badge--warn',
      'rejected' => 'business-moderation-badge--danger',
      default => 'business-moderation-badge--info',
    };
  } elseif ($kind === 'score') {
    $score = (int) $normalized;
    $class = $score >= 60
      ? 'business-moderation-badge--danger'
      : ($score >= 21 ? 'business-moderation-badge--warn' : 'business-moderation-badge--success');
  }

  return '<span class="business-moderation-badge ' . $class . '">'
    . htmlspecialchars($value, ENT_QUOTES, 'UTF-8')
    . '</span>';
}

function businessModerationFormatTimestamp(string $value): string
{
  $trimmed = trim($value);
  if ($trimmed === '') {
    return '—';
  }

  $timestamp = strtotime($trimmed);

  return $timestamp !== false ? date('M j, Y g:i A', $timestamp) : $trimmed;
}

$currentPage = 'PAGE_ADMIN';
$pageTitle = Strings::i18n('ADMIN_BUSINESS_MODERATION_TITLE') . ' - [PayCal]';
$pageLabel = Strings::i18n('ADMIN_BUSINESS_MODERATION_TITLE');

Authentication::redirectHomeIfUnauthenticated();
AdminSurface::redirectHomeIfPageUnavailable('/admin/business-moderation/');

$flashOk = '';
$flashError = '';
$flashType = '';
$flashTitle = '';
$flashDetail = '';

/** @var array<string, string> $actionLabels */
$actionLabels = [
  'approve_listing' => 'Approved public listing',
  'reject_name' => 'Rejected public name',
  'hide_listing' => 'Hid public listing',
  'suspend_business' => 'Suspended business',
  'restore_business' => 'Restored business for review',
  'mark_owner_trusted' => 'Marked owner as trusted',
  'rebuild_search_index' => 'Rebuilt public search index',
];

$requestMethod = isset($_SERVER['REQUEST_METHOD']) && is_string($_SERVER['REQUEST_METHOD'])
  ? strtoupper($_SERVER['REQUEST_METHOD'])
  : 'GET';
$currentUser = \PayCal\Domain\User::current();

if ($requestMethod === 'POST') {
  $csrfTokenRaw = $_POST['csrf_token'] ?? '';
  $csrfToken = is_scalar($csrfTokenRaw) ? trim((string) $csrfTokenRaw) : '';
  $action = InputSanitizer::postString('action');
  $businessId = InputSanitizer::postString('business_id');
  $adminUUID = $currentUser->user_uuid;
  $businessName = $businessId !== ''
    ? trim((string) (Database::hget(Keys::BUSINESS . ':' . $businessId, 'name') ?: ''))
    : '';
  $actionLabel = $actionLabels[$action] ?? 'Updated business';
  $displayName = $businessName !== '' ? $businessName : 'Unknown business';

  if ($csrfToken === '' || !$currentUser->verifyFormNonce('general', $csrfToken)) {
    $flashError = 'Action blocked: invalid CSRF token.';
    $flashTitle = 'Action blocked';
    $flashDetail = 'Refresh the page and try the moderation action again.';
  } elseif ($businessId === '') {
    $flashError = 'Missing business ID.';
  } else {
    $ok = match ($action) {
      'rebuild_search_index' => true,
      'approve_listing' => BusinessModerationService::approveListing($businessId, $adminUUID),
      'reject_name' => BusinessModerationService::rejectName($businessId, $adminUUID),
      'hide_listing' => BusinessModerationService::hideListing($businessId, $adminUUID),
      'suspend_business' => BusinessModerationService::suspendBusiness(
        $businessId,
        $adminUUID,
        InputSanitizer::postString('reason') ?: 'manual_admin_decision',
      ),
      'restore_business' => BusinessModerationService::restoreBusiness($businessId, $adminUUID),
      'mark_owner_trusted' => BusinessModerationService::markOwnerTrusted($businessId, $adminUUID),
      default => false,
    };

    if ($ok) {
      if ($action === 'rebuild_search_index') {
        $indexedCount = BusinessSearchIndex::rebuildAll();
        $flashOk = $actionLabel . '.';
        $flashTitle = $actionLabel;
        $flashDetail = $indexedCount . ' business' . ($indexedCount === 1 ? '' : 'es') . ' are now searchable in the Business Browser.';
      } else {
        $flashOk = $actionLabel . ' for “' . $displayName . '”.';
        $flashTitle = $actionLabel;
        $flashDetail = '“' . $displayName . '” (' . $businessId . ') was updated successfully.';
      }
    } else {
      $flashError = $actionLabel . ' failed for “' . $displayName . '”.';
      $flashTitle = $actionLabel . ' failed';
      $flashDetail = 'Could not update “' . $displayName . '” (' . $businessId . '). Check logs or try again.';
    }
  }
}

$actionNonce = $currentUser->generateFormNonce('general');
$queue = BusinessModerationService::listQueue(200);
$queueCount = count($queue);

if ($flashOk !== '') {
  $flashType = 'success';
  $flashDetail .= ' Queue now has ' . $queueCount . ' business' . ($queueCount === 1 ? '' : 'es') . ' pending review.';
} elseif ($flashError !== '') {
  $flashType = 'error';
  if ($flashTitle === '') {
    $flashTitle = 'Action failed';
  }
  if ($flashDetail === '') {
    $flashDetail = $flashError;
  }
}

$flashMessage = $flashTitle !== '' ? ($flashTitle . ' ' . $flashDetail) : $flashError;

require_once HTML . '/header.php';

$cspNonceRaw = $_SERVER['CSP_NONCE'] ?? '';
$cspNonce = is_scalar($cspNonceRaw) ? (string) $cspNonceRaw : '';
echo '<link rel="stylesheet" href="' . htmlspecialchars(Render::cssURL('admin'), ENT_QUOTES, 'UTF-8') . '" nonce="' . htmlspecialchars($cspNonce, ENT_QUOTES, 'UTF-8') . '">' . PHP_EOL;
echo '<link rel="stylesheet" href="' . htmlspecialchars(Render::cssURL('admin/business-moderation'), ENT_QUOTES, 'UTF-8') . '" nonce="' . htmlspecialchars($cspNonce, ENT_QUOTES, 'UTF-8') . '">' . PHP_EOL;

if ($flashMessage !== '') {
  $flashJson = json_encode([
    'type' => $flashType,
    'title' => $flashTitle,
    'detail' => $flashDetail,
    'message' => trim($flashTitle . ' ' . $flashDetail),
  ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
  echo '<script type="application/json" id="business-moderation-flash-data" nonce="' . htmlspecialchars($cspNonce, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($flashJson, ENT_QUOTES, 'UTF-8') . '</script>' . PHP_EOL;
}

$feedbackVisible = $flashType !== '' && ($flashTitle !== '' || $flashDetail !== '');
$feedbackIcon = $flashType === 'error' ? '!' : '✓';
?>
<section class="business-moderation admin-surface pad_lg" aria-label="Business Moderation Queue">
  <header class="business-moderation__header panel w100 pad_md mar_sm">
    <div>
      <h1><?= htmlspecialchars(Strings::i18n('ADMIN_BUSINESS_MODERATION_TITLE'), ENT_QUOTES, 'UTF-8') ?></h1>
      <p>Review business names before they appear in the public Business Browser. Payment grants private capability; moderation grants public visibility.</p>
    </div>
    <p class="business-moderation__badge <?php echo $queueCount > 0 ? 'business-moderation__badge--active' : 'business-moderation__badge--idle'; ?>">
      <?php echo (int) $queueCount; ?> pending
    </p>
  </header>

  <div
    id="business-moderation-feedback"
    class="business-moderation-feedback panel w100 mar_sm business-moderation-feedback--<?php echo htmlspecialchars($flashType !== '' ? $flashType : 'success', ENT_QUOTES, 'UTF-8'); ?><?php echo $feedbackVisible ? ' is-visible' : ''; ?>"
    role="status"
    aria-live="polite"
    aria-atomic="true"
  >
<?php if ($feedbackVisible): ?>
    <div class="business-moderation-feedback__icon" aria-hidden="true"><?php echo htmlspecialchars($feedbackIcon, ENT_QUOTES, 'UTF-8'); ?></div>
    <div class="business-moderation-feedback__body">
      <p class="business-moderation-feedback__title"><?php echo htmlspecialchars($flashTitle, ENT_QUOTES, 'UTF-8'); ?></p>
      <p class="business-moderation-feedback__detail"><?php echo htmlspecialchars($flashDetail, ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
<?php endif; ?>
  </div>

  <section class="panel w100 pad_md mar_sm" aria-label="Search index maintenance">
    <h2>Search Index</h2>
    <p class="business-moderation-queue__meta">Rebuild the public Business Browser index from approved, listed businesses.</p>
    <form method="POST" action="/admin/business-moderation/" class="business-moderation-actions">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($actionNonce, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="business_id" value="system">
      <button type="submit" name="action" value="rebuild_search_index" class="btn btn_secondary btn_sm">Rebuild search index</button>
    </form>
  </section>

  <section class="panel w100 pad_md mar_sm" aria-label="Moderation queue">
    <div class="business-moderation-queue__head">
      <h2>Pending Review</h2>
      <p class="business-moderation-queue__meta"><?php echo (int) $queueCount; ?> business<?php echo $queueCount === 1 ? '' : 'es'; ?> awaiting review</p>
    </div>
<?php if ($queue === []): ?>
    <div class="business-moderation-empty" role="status">
      <p class="business-moderation-empty__title">Queue is clear</p>
      <p class="business-moderation-empty__text">No businesses are waiting for name review. New submissions will appear here after a shared business is created or renamed.</p>
    </div>
<?php else: ?>
    <div class="business-moderation-table-wrap">
      <table class="business-moderation-table" aria-label="Business moderation queue">
        <thead>
          <tr>
            <th scope="col">Business</th>
            <th scope="col">Pending name</th>
            <th scope="col">Owner</th>
            <th scope="col">Paid</th>
            <th scope="col">Visibility</th>
            <th scope="col">Moderation</th>
            <th scope="col">Score</th>
            <th scope="col">Reasons</th>
            <th scope="col">Created</th>
            <th scope="col">Actions</th>
          </tr>
        </thead>
        <tbody>
<?php foreach ($queue as $row): ?>
          <tr>
            <td class="business-moderation-table__business"><?php echo htmlspecialchars((string) ($row['approved_display_name'] ?? $row['business_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
            <td class="business-moderation-table__reasons"><?php echo htmlspecialchars((string) ($row['pending_display_name'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars((string) ($row['owner_email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo businessModerationBadge((string) ($row['paid_status'] ?? ''), 'paid'); ?></td>
            <td><?php echo businessModerationBadge((string) ($row['visibility'] ?? ''), 'visibility'); ?></td>
            <td><?php echo businessModerationBadge((string) ($row['moderation_status'] ?? ''), 'moderation'); ?></td>
            <td><?php echo businessModerationBadge((string) ($row['moderation_score'] ?? '0'), 'score'); ?></td>
            <td class="business-moderation-table__reasons"><?php echo htmlspecialchars((string) ($row['moderation_reasons'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></td>
            <td class="business-moderation-table__mono"><?php echo htmlspecialchars(businessModerationFormatTimestamp((string) ($row['created_at'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></td>
            <td>
              <form method="POST" action="/admin/business-moderation/" class="business-moderation-actions">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($actionNonce, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="business_id" value="<?php echo htmlspecialchars((string) ($row['business_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit" name="action" value="approve_listing" class="btn btn_primary btn_sm">Approve</button>
                <button type="submit" name="action" value="reject_name" class="btn btn_sm">Reject</button>
                <button type="submit" name="action" value="hide_listing" class="btn btn_sm">Hide</button>
                <button type="submit" name="action" value="suspend_business" class="btn btn_delete btn_sm">Suspend</button>
                <button type="submit" name="action" value="mark_owner_trusted" class="btn btn_sm">Trust</button>
              </form>
            </td>
          </tr>
<?php endforeach; ?>
        </tbody>
      </table>
    </div>
<?php endif; ?>
  </section>
</section>
<?php
echo '<script type="module" src="' . htmlspecialchars(Environment::appURL('js/admin/business-moderation.php'), ENT_QUOTES, 'UTF-8') . '?v=' . htmlspecialchars(Environment::appVersion(), ENT_QUOTES, 'UTF-8') . '" nonce="' . htmlspecialchars($cspNonce, ENT_QUOTES, 'UTF-8') . '"></script>' . PHP_EOL;
require_once HTML . '/footer.php';
