<?php declare(strict_types=1);

/**
 * admin/user-roles/index.php — Auditor Role Management
 *
 * Purpose: SUPERADMIN-only page for granting and revoking the AUDITOR role.
 * Once a user registers normally, a superadmin can promote them to AUDITOR here
 * so they gain read access to the /soc Auditor Portal.
 *
 * Access: Requires AdminSurface (admin auth) + User::isSuperAdmin() (rank 2000).
 * Plain ADMIN is denied — only the singleton SUPERADMIN may manage auditor access.
 *
 * Actions (POST):
 *  - action=lookup  : look up a registered user by email and show their current role
 *  - action=assign  : grant AUDITOR role to a user (by email)
 *  - action=revoke  : revoke AUDITOR role back to USER (by UUID)
 *
 * Why here: Keeps role mutation behind an admin surface + superadmin guard so
 * no ordinary admin can touch auditor access. Separate from the main admin
 * dashboard to scope the risk surface.
 */

use PayCal\Domain\AdminSurface;
use PayCal\Domain\Authentication;
use PayCal\Domain\Config\Environment;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;
use PayCal\Domain\Enums\AuthLevel;
use PayCal\Domain\InputSanitizer;
use PayCal\Domain\Render;
use PayCal\Domain\User;
use PayCal\Domain\UserRepository;

require_once '../../config.php';

$currentPage = 'PAGE_ADMIN';
$pageTitle   = 'Auditor Role Management - [PayCal]';
$pageLabel   = 'Auditor Role Management';

Authentication::redirectHomeIfUnauthenticated();
AdminSurface::redirectHomeIfPageUnavailable('/admin/user-roles/');
if (!User::isSuperAdmin()) {
  header('Location: ' . Environment::appURL('/admin/'));
  exit;
}

// ── Action handling ───────────────────────────────────────────────────────────

$flashOk    = '';
$flashError = '';
$lookupUser = null; // User|null — result of a lookup action

$requestMethod = isset($_SERVER['REQUEST_METHOD']) && is_string($_SERVER['REQUEST_METHOD'])
  ? strtoupper($_SERVER['REQUEST_METHOD'])
  : 'GET';

if ($requestMethod === 'POST') {
  $action = InputSanitizer::postString('action');

  if ($action === 'lookup') {
    $email  = InputSanitizer::postString('email');
    $uuid   = UserRepository::getUUIDFromEmail($email);
    if ('' === $uuid) {
      $flashError = 'No account found for that email address.';
    } else {
      $lookupUser = UserRepository::find($uuid);
      if ($lookupUser === null) {
        $flashError = 'User record not found for that email.';
      }
    }

  } elseif ($action === 'assign') {
    $email = InputSanitizer::postString('email');
    $uuid  = UserRepository::getUUIDFromEmail($email);
    if ('' === $uuid) {
      $flashError = 'No account found for that email address.';
    } else {
      $target = UserRepository::find($uuid);
      if ($target === null) {
        $flashError = 'User record not found.';
      } elseif ($target->auth_level === AuthLevel::SUPERADMIN) {
        $flashError = 'Cannot change the auth level of a SUPERADMIN.';
      } else {
        UserRepository::setAuthLevel($uuid, AuthLevel::AUDITOR);
        $flashOk = 'AUDITOR role granted to ' . htmlspecialchars($target->email, ENT_QUOTES, 'UTF-8') . '.';
      }
    }

  } elseif ($action === 'revoke') {
    $uuid = InputSanitizer::postString('uuid');
    if ('' === $uuid) {
      $flashError = 'Missing user UUID.';
    } else {
      $target = UserRepository::find($uuid);
      if ($target === null) {
        $flashError = 'User record not found.';
      } elseif ($target->auth_level !== AuthLevel::AUDITOR) {
        $flashError = 'User is not currently an AUDITOR.';
      } else {
        UserRepository::setAuthLevel($uuid, AuthLevel::USER);
        $flashOk = 'AUDITOR access revoked for ' . htmlspecialchars($target->email, ENT_QUOTES, 'UTF-8') . '. Role reset to USER.';
      }
    }
  }
}

// ── Load current auditors ─────────────────────────────────────────────────────

/** @var User[] $currentAuditors */
$currentAuditors = [];

try {
  foreach (Database::scanKeys(Keys::USER . ':*') as $userKey) {
    $uuid = substr((string) $userKey, strlen(Keys::USER . ':'));
    if ('' === $uuid) {
      continue;
    }
    $levelRaw = Database::hget(Keys::USER . ':' . $uuid, 'auth_level');
    if ((string) $levelRaw !== AuthLevel::AUDITOR->value) {
      continue;
    }
    $u = UserRepository::find($uuid);
    if ($u !== null) {
      $currentAuditors[] = $u;
    }
  }
} catch (\Throwable) {
  // Non-fatal — show an empty list rather than crashing the page.
}

// ── Render ────────────────────────────────────────────────────────────────────

require_once HTML . '/header.php';

$cspNonceRaw = $_SERVER['CSP_NONCE'] ?? '';
$cspNonce    = is_scalar($cspNonceRaw) ? (string) $cspNonceRaw : '';
echo PHP_EOL . '<link rel="stylesheet" href="' . htmlspecialchars(Render::cssURL('admin'), ENT_QUOTES, 'UTF-8') . '" nonce="' . htmlspecialchars($cspNonce, ENT_QUOTES, 'UTF-8') . '">' . PHP_EOL;
?>
<section class="admin-surface pad_lg" aria-label="Auditor Role Management">

  <header class="panel w100 pad_md mar_sm">
    <h1>Auditor Role Management</h1>
    <p>Grant or revoke the <strong>AUDITOR</strong> role for registered users.
       Auditors may sign in and view the <a href="/soc/">/soc Auditor Portal</a>
       but cannot access the admin panel or any other privileged surfaces.</p>
  </header>

<?php if ($flashOk !== ''): ?>
  <div class="panel w100 pad_sm mar_sm" role="status" style="border-left: 4px solid var(--color-success, #2da44e);">
    <p><?= htmlspecialchars($flashOk, ENT_QUOTES, 'UTF-8') ?></p>
  </div>
<?php endif; ?>
<?php if ($flashError !== ''): ?>
  <div class="panel w100 pad_sm mar_sm" role="alert" style="border-left: 4px solid var(--color-danger, #cf222e);">
    <p><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?></p>
  </div>
<?php endif; ?>

  <!-- ── Active Auditors ── -->
  <section class="panel w100 pad_md mar_sm" aria-label="Active Auditors">
    <h2>Active Auditors</h2>
<?php if ($currentAuditors === []): ?>
    <p class="text_muted">No users currently hold the AUDITOR role.</p>
<?php else: ?>
    <table class="data-table w100" aria-label="Auditor accounts">
      <thead>
        <tr>
          <th scope="col">Full Name</th>
          <th scope="col">Email</th>
          <th scope="col">UUID</th>
          <th scope="col">Action</th>
        </tr>
      </thead>
      <tbody>
<?php foreach ($currentAuditors as $auditor): ?>
        <tr>
          <td><?= htmlspecialchars($auditor->full_name, ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars($auditor->email,     ENT_QUOTES, 'UTF-8') ?></td>
          <td class="text_mono text_sm"><?= htmlspecialchars($auditor->user_uuid, ENT_QUOTES, 'UTF-8') ?></td>
          <td>
            <form method="POST" action="/admin/user-roles/" class="inline-form">
              <input type="hidden" name="action" value="revoke">
              <input type="hidden" name="uuid"   value="<?= htmlspecialchars($auditor->user_uuid, ENT_QUOTES, 'UTF-8') ?>">
              <button type="submit" class="btn btn_delete btn_sm"
                      onclick="return confirm('Revoke AUDITOR access for <?= htmlspecialchars(addslashes($auditor->email), ENT_QUOTES, 'UTF-8') ?>?')">
                Revoke
              </button>
            </form>
          </td>
        </tr>
<?php endforeach; ?>
      </tbody>
    </table>
<?php endif; ?>
  </section>

  <!-- ── Assign Auditor ── -->
  <section class="panel w100 pad_md mar_sm" aria-label="Assign Auditor Access">
    <h2>Assign Auditor Access</h2>
    <p class="text_muted">Enter the email address of a registered user to look them up, then grant the AUDITOR role.</p>

    <form method="POST" action="/admin/user-roles/" class="form-stack">
      <input type="hidden" name="action" value="lookup">
      <div class="form-row">
        <label for="lookup-email">Email address</label>
        <input id="lookup-email" name="email" type="email" required
               placeholder="auditor@example.com"
               value="<?= isset($lookupUser) ? htmlspecialchars($lookupUser->email, ENT_QUOTES, 'UTF-8') : '' ?>">
      </div>
      <button type="submit" class="btn">Look up user</button>
    </form>

<?php if ($lookupUser !== null): ?>
    <div class="panel pad_sm mar_sm" aria-live="polite">
      <h3>Found user</h3>
      <dl>
        <dt>Full Name</dt><dd><?= htmlspecialchars($lookupUser->full_name, ENT_QUOTES, 'UTF-8') ?></dd>
        <dt>Email</dt>     <dd><?= htmlspecialchars($lookupUser->email,     ENT_QUOTES, 'UTF-8') ?></dd>
        <dt>UUID</dt>      <dd class="text_mono"><?= htmlspecialchars($lookupUser->user_uuid, ENT_QUOTES, 'UTF-8') ?></dd>
        <dt>Current role</dt>
        <dd><strong><?= htmlspecialchars(strtoupper($lookupUser->auth_level->value), ENT_QUOTES, 'UTF-8') ?></strong></dd>
      </dl>

<?php if ($lookupUser->auth_level === AuthLevel::AUDITOR): ?>
      <p>This user already has the AUDITOR role. Use the Revoke button in the table above to remove it.</p>
<?php elseif ($lookupUser->auth_level === AuthLevel::SUPERADMIN): ?>
      <p>SUPERADMIN accounts cannot be modified here.</p>
<?php else: ?>
      <form method="POST" action="/admin/user-roles/" class="inline-form">
        <input type="hidden" name="action" value="assign">
        <input type="hidden" name="email"  value="<?= htmlspecialchars($lookupUser->email, ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit" class="btn btn_primary">
          Grant AUDITOR access
        </button>
      </form>
<?php endif; ?>
    </div>
<?php endif; ?>
  </section>

</section>
<?php
require_once HTML . '/footer.php';
