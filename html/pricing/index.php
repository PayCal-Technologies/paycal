<?php

declare(strict_types=1);

use PayCal\Domain\Authentication;
use PayCal\Domain\BillingProvider;
use PayCal\Domain\SubscriptionRepository;
use PayCal\Domain\User;

/**
 * Public pricing page.
 *
 * Presents PayCal Free, Premium, and Business tiers with a feature matrix.
 */
$currentPage  = 'PAGE_PRICING';
$pageTitle    = 'Pricing - [PayCal]';
$pageLanguage = 'en-CA';

require_once '../config.php';

$isAuthenticated = Authentication::getCookie() !== '';
$currentUser = $isAuthenticated ? User::current() : null;
$csrfNonce = $currentUser instanceof User ? $currentUser->generateFormNonce('settings') : '';
$billingProvider = BillingProvider::current();
$isStripeBilling = $billingProvider === BillingProvider::STRIPE;
$hasActivePremium = $currentUser instanceof User ? SubscriptionRepository::isPremiumActive($currentUser->user_uuid) : false;
$hasActiveBusiness = $currentUser instanceof User ? SubscriptionRepository::isBusinessActive($currentUser->user_uuid) : false;

require_once HTML.'/header.php';

$plans = [
  [
    'name' => 'Free',
    'price' => '$0',
    'cadence' => 'CAD/month',
    'summary' => 'Core personal work, wage, calendar, and PDF records with no subscription.',
    'cta' => 'Start free',
    'href' => '/auth/signup/?tier=free',
  ],
  [
    'name' => 'Premium',
    'price' => '$4.99',
    'cadence' => 'CAD/month',
    'summary' => 'Forecasting, spreadsheet/text exports, advanced graphs, and deeper personal reports for individuals.',
    'plan' => 'premium',
    'cta' => $hasActivePremium ? 'Premium active' : 'Upgrade to Premium',
    'href' => '/auth/signup/?tier=premium',
    'disabled' => $hasActivePremium,
    'featured' => true,
  ],
  [
    'name' => 'Business',
    'price' => '$29.99',
    'cadence' => 'CAD/month total',
    'summary' => 'One flat workspace plan for shared member visibility, business reports, audit tools, and aggregate payroll analysis.',
    'contrast' => 'Compared with Premium: adds shared workspace.',
    'plan' => 'business',
    'cta' => $hasActiveBusiness ? 'Business active' : 'Upgrade to Business',
    'href' => '/auth/signup/?tier=business',
    'disabled' => $hasActiveBusiness,
  ],
];

$features = [
  ['Core tracking', 'Work hours, sites, wages, calendar, and personal earnings.', 'Included', 'Included', 'Included'],
  ['Pay calculations', 'Regular, overtime, net pay, deductions, and year-to-date summaries.', 'Included', 'Included', 'Included'],
  ['Download formats', 'Export payroll records for your own archive or accountant.', 'PDF only', 'PDF, XLSX, CSV, TXT', 'PDF, XLSX, CSV, TXT'],
  ['Forecasting', 'Project expected gross and net earnings before the work is complete.', 'Not included', 'Included', 'Included'],
  ['Advanced graphs', 'Additional visual breakdowns for overtime, net trends, taxes, and site mix.', 'Basic summaries', 'Included', 'Included'],
  ['Financial reports', 'Deeper monthly, annual, tax, and earnings variance reports.', 'Basic reports', 'Advanced personal reports', 'Advanced business reports'],
  ['Business workspace', 'Create a shared workspace for payroll visibility and operations.', 'Not included', 'Not included', 'Included'],
  ['Member financial viewing', 'Review member work and finance records with role-based access.', 'Not included', 'Not included', 'Included'],
  ['Aggregate analysis', 'Analyze group totals, labour cost drivers, overtime exposure, and site-level performance.', 'Not included', 'Not included', 'Included'],
  ['Membership limit', 'Maximum active members in one business workspace, including the owner.', 'Not included', 'Not included', '100 active members total'],
  ['Role controls', 'Owner, manager, member, and viewer-style access controls for shared work.', 'Not included', 'Not included', 'Included'],
  ['Audit-ready records', 'Structured exports and histories built for payroll review.', 'Personal records', 'Personal records', 'Business records'],
];
?>

<div class="pricing_page" id="pricing-page">
  <section class="pricing_hero" aria-labelledby="pricing-heading">
    <p class="pricing_eyebrow">PayCal pricing</p>
    <h1 id="pricing-heading">Free personal tracking, Premium planning, and Business workspaces.</h1>
    <p class="pricing_deck">Use PayCal Free for core work and pay records, Premium for deeper personal forecasting and exports, or Business for shared member visibility and aggregate payroll analysis.</p>
  </section>

  <section class="pricing_cards" aria-label="Pricing plans">
    <?php foreach ($plans as $plan): ?>
      <article class="pricing_card<?php echo !empty($plan['featured']) ? ' pricing_card_featured' : ''; ?>">
        <div>
          <h2><?php echo htmlspecialchars($plan['name'], ENT_QUOTES, 'UTF-8'); ?></h2>
          <p class="pricing_card_summary"><?php echo htmlspecialchars($plan['summary'], ENT_QUOTES, 'UTF-8'); ?></p>
          <?php if (isset($plan['contrast'])): ?>
            <span class="pricing_contrast_label"><?php echo htmlspecialchars((string) $plan['contrast'], ENT_QUOTES, 'UTF-8'); ?></span>
          <?php endif; ?>
        </div>
        <p class="pricing_price">
          <span><?php echo htmlspecialchars($plan['price'], ENT_QUOTES, 'UTF-8'); ?></span>
          <small><?php echo htmlspecialchars($plan['cadence'], ENT_QUOTES, 'UTF-8'); ?></small>
        </p>
        <?php if ($isAuthenticated && $isStripeBilling && isset($plan['plan'])): ?>
          <button
            type="button"
            id="billing_upgrade_<?php echo htmlspecialchars((string) $plan['plan'], ENT_QUOTES, 'UTF-8'); ?>_btn"
            class="btn <?php echo !empty($plan['featured']) ? 'btn_primary' : 'btn_secondary'; ?>"
            data-billing-plan="<?php echo htmlspecialchars((string) $plan['plan'], ENT_QUOTES, 'UTF-8'); ?>"
            <?php echo !empty($plan['disabled']) ? 'disabled' : ''; ?>
          >
            <?php echo htmlspecialchars($plan['cta'], ENT_QUOTES, 'UTF-8'); ?>
          </button>
          <div
            id="billing_upgrade_<?php echo htmlspecialchars((string) $plan['plan'], ENT_QUOTES, 'UTF-8'); ?>_status"
            class="status_text compact_hint"
            role="status"
            aria-live="polite"
          ></div>
        <?php elseif (!$isAuthenticated): ?>
          <a
            class="btn <?php echo !empty($plan['featured']) ? 'btn_primary' : 'btn_secondary'; ?> pricing_plan_link"
            href="<?php echo htmlspecialchars((string) ($plan['href'] ?? '/auth/'), ENT_QUOTES, 'UTF-8'); ?>"
          >
            <?php echo htmlspecialchars((string) ($plan['cta'] ?? 'Sign in'), ENT_QUOTES, 'UTF-8'); ?>
          </a>
        <?php elseif (!isset($plan['plan'])): ?>
          <p class="pricing_guest_note">Included with every PayCal account.</p>
        <?php else: ?>
          <p class="pricing_guest_note">Billing is managed from your account settings.</p>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>
  </section>

  <?php if ($isAuthenticated && $isStripeBilling): ?>
    <section
      class="pricing_billing_runtime"
      id="panel-billing"
      aria-label="Pricing checkout"
      data-billing-hydrated="false"
      data-billing-provider="<?php echo htmlspecialchars($billingProvider, ENT_QUOTES, 'UTF-8'); ?>"
    >
      <input type="hidden" id="settings_csrf_token" name="csrf_token" value="<?php echo htmlspecialchars($csrfNonce, ENT_QUOTES, 'UTF-8'); ?>">
      <div id="billing_status_sr" class="visually_hidden" role="status" aria-live="polite" aria-atomic="true"></div>
      <div id="billing_free_view" hidden></div>
      <div id="billing_premium_view" hidden></div>
    </section>
  <?php endif; ?>

  <section class="pricing_matrix_section" aria-labelledby="pricing-matrix-heading">
    <div class="pricing_section_header">
      <h2 id="pricing-matrix-heading">Feature comparison</h2>
      <p>Premium is priced for individual value. Business is one flat monthly price for one workspace, up to 100 active members total including the owner.</p>
    </div>

    <div class="pricing_matrix_wrap">
      <table class="pricing_matrix">
        <thead>
          <tr>
            <th scope="col">Feature</th>
            <th scope="col">Free</th>
            <th scope="col">Premium</th>
            <th scope="col">Business</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($features as $feature): ?>
            <tr>
              <th scope="row">
                <span><?php echo htmlspecialchars($feature[0], ENT_QUOTES, 'UTF-8'); ?></span>
                <small><?php echo htmlspecialchars($feature[1], ENT_QUOTES, 'UTF-8'); ?></small>
              </th>
              <td><?php echo htmlspecialchars($feature[2], ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?php echo htmlspecialchars($feature[3], ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?php echo htmlspecialchars($feature[4], ENT_QUOTES, 'UTF-8'); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <section class="pricing_note" aria-labelledby="pricing-note-heading">
    <h2 id="pricing-note-heading">Which plan should I choose?</h2>
    <div class="pricing_note_grid">
      <p><strong>Free</strong> is for keeping personal work and pay records with PDF downloads.</p>
      <p><strong>Premium</strong> is for regular users who want forecasting, spreadsheet/text exports, stronger graphs, and deeper personal financial reports.</p>
      <p><strong>Business</strong> is for owners and managers who need member visibility, aggregate payroll analysis, group reports, and up to 100 active members total for one flat price. The owner counts toward that limit, so a full workspace is one owner plus 99 additional members.</p>
    </div>
  </section>
</div>

<?php if ($isAuthenticated && $isStripeBilling): ?>
  <script type="module" nonce="<?php echo htmlspecialchars((string) ($_SERVER['CSP_NONCE'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
    import { initializeBillingSection } from '/js/core/billing.js';

    await initializeBillingSection({
      successUrl: '/pricing/?billing=success',
      cancelUrl: '/pricing/?billing=cancel',
      returnUrl: '/pricing/',
    });
  </script>
<?php endif; ?>

<?php require_once HTML.'/footer.php'; ?>
