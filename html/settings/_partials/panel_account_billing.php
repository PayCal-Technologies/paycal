<?php declare(strict_types=1);

namespace PayCal\Domain;

$billingPlanName = $hasActiveBusiness
  ? 'Business'
  : ($hasActivePremium ? 'Premium' : 'Free');
$billingManagerLabel = $isStripeBilling ? 'Stripe' : 'Local';
?>

  <section class="panel subscription_command_center" id="panel-billing" aria-label="Current plan" title="<?php echo settings_index_i18n('PROFILE_BILLING_PANEL_HELP'); ?>" data-hover-help="<?php echo settings_index_i18n('PROFILE_BILLING_PANEL_HELP'); ?>" data-billing-hint="<?php echo htmlspecialchars($billingHint, ENT_QUOTES, 'UTF-8'); ?>" data-billing-hydrated="false" data-billing-provider="<?php echo htmlspecialchars($billingProvider, ENT_QUOTES, 'UTF-8'); ?>" data-account-timezone="<?php echo htmlspecialchars((string) ($user->timezone ?? 'UTC'), ENT_QUOTES, 'UTF-8'); ?>">
    <div id="billing_status_sr" class="visually_hidden" role="status" aria-live="polite" aria-atomic="true"></div>

    <div id="billing_free_view" class="billing_shell"<?php if ($hasPaidSubscription) echo ' hidden'; ?>>
      <section class="subscription_plan_card" aria-label="Current plan">
        <p class="subscription_kicker">Current Plan</p>
        <h3>Free</h3>
        <?php if (!$isStripeBilling) { ?>
          <p class="help_text"><?php echo settings_index_i18n('PROFILE_BILLING_PUBLIC_CORE_NOTE'); ?></p>
        <?php } ?>
        <dl class="subscription_facts">
          <div><dt>Billing</dt><dd>No paid subscription</dd></div>
        </dl>
        <div class="subscription_actions">
          <button type="button" id="billing_upgrade_premium_btn" class="btn btn_primary">Upgrade to Premium</button>
          <button type="button" id="billing_upgrade_business_btn" class="btn btn_secondary">Upgrade to Business</button>
        </div>
        <div id="billing_upgrade_premium_status" class="status_text compact_hint" role="status" aria-live="polite"></div>
        <div id="billing_upgrade_business_status" class="status_text compact_hint" role="status" aria-live="polite"></div>
      </section>
    </div>

    <div id="billing_premium_view" class="billing_shell"<?php if (!$hasPaidSubscription) echo ' hidden'; ?>>
      <section class="subscription_plan_card" aria-label="<?php echo settings_index_i18n('PROFILE_BILLING_PREMIUM_ARIA'); ?>">
        <p class="subscription_kicker">Current Plan</p>
        <h3><span id="billing_plan_label"><?php echo htmlspecialchars($billingPlanName, ENT_QUOTES, 'UTF-8'); ?></span></h3>
        <span id="billing_plan_status_badge" class="badge" hidden></span>
        <dl class="subscription_facts">
          <div><dt>Active</dt><dd id="billing_start_date">&#8212;</dd></div>
          <div><dt>Billing</dt><dd><?php echo htmlspecialchars($billingManagerLabel, ENT_QUOTES, 'UTF-8'); ?></dd></div>
          <div><dt>Role</dt><dd><?php echo htmlspecialchars($billingBusinessRoleLabel, ENT_QUOTES, 'UTF-8'); ?></dd></div>
          <div><dt>Group</dt><dd><?php echo htmlspecialchars($billingBusinessSummary, ENT_QUOTES, 'UTF-8'); ?></dd></div>
        </dl>

        <p class="billing_cancel_notice" id="billing_cancel_notice" hidden>
          <?php echo settings_index_i18n('PROFILE_BILLING_CANCEL_SCHEDULED_PREFIX'); ?>
          <span class="billing_datetime_anchor">
            <button
              type="button"
              id="billing_cancel_date_trigger"
              class="billing_datetime_trigger"
              aria-haspopup="dialog"
              aria-controls="billing_datetime_popover"
              aria-expanded="false"
            >
              <span id="billing_cancel_date">&#8212;</span>
              <span class="visually_hidden"><?php echo settings_index_i18n('PROFILE_BILLING_CANCEL_DATE_TRIGGER'); ?></span>
            </button>
            <span
              id="billing_datetime_popover"
              class="billing_datetime_popover"
              role="dialog"
              aria-modal="false"
              aria-label="<?php echo settings_index_i18n('PROFILE_BILLING_CANCEL_DATE_DETAILS_ARIA'); ?>"
              hidden
            >
              <span class="billing_datetime_popover_title"><?php echo settings_index_i18n('PROFILE_BILLING_TIMEZONES'); ?></span>
              <span class="billing_datetime_popover_rows" id="billing_datetime_popover_rows"></span>
            </span>
          </span>
          . <?php echo settings_index_i18n('PROFILE_BILLING_CANCEL_SCHEDULED_SUFFIX'); ?>
        </p>

        <div class="subscription_actions">
          <button type="button" id="billing_upgrade_business_plan_btn" class="btn btn_primary"<?php if ($hasActiveBusiness) echo ' hidden'; ?>>Upgrade to Business</button>
          <button type="button" id="billing_downgrade_premium_btn" class="btn btn_secondary"<?php if (!$hasActiveBusiness) echo ' hidden'; ?>>Downgrade to Premium</button>
          <button type="button" id="billing_downgrade_free_btn" class="btn btn_secondary">Downgrade to Free</button>
          <?php if ($isStripeBilling || !$hasActiveBusiness) { ?>
            <button type="button" id="billing_portal_btn" class="btn btn_secondary"><?php echo $isStripeBilling ? 'Manage Billing' : settings_index_i18n('PROFILE_BILLING_DISABLE_PREMIUM'); ?></button>
          <?php } ?>
        </div>
        <div id="billing_upgrade_business_plan_status" class="status_text compact_hint" role="status" aria-live="polite"></div>
        <div id="billing_downgrade_premium_status" class="status_text compact_hint" role="status" aria-live="polite"></div>
        <div id="billing_downgrade_free_status" class="status_text compact_hint" role="status" aria-live="polite"></div>
        <div id="billing_portal_status" class="status_text compact_hint" role="status" aria-live="polite"></div>
        <p id="business-upgrade-status" class="business-upgrade-status" hidden>Now upgraded to Business!</p>
      </section>

      <?php if ($isStripeBilling) { ?>
        <dialog id="billing_downgrade_free_dialog" data-dialog-invoker-bridge data-dialog-close-tts="<?php echo settings_index_i18n('PROFILE_BILLING_DOWNGRADE_FREE_DIALOG_TITLE'); ?>" class="subscription_downgrade_dialog" aria-labelledby="billing_downgrade_free_dialog_title">
          <form method="dialog">
            <h3 id="billing_downgrade_free_dialog_title">Downgrade to Free?</h3>
            <p id="billing_downgrade_free_dialog_body">Free removes paid plan access immediately. You may lose access to paid reports, exports, business records, sites, group roles, listings, and audit tools.</p>
            <div class="subscription_dialog_actions">
              <button type="button" id="billing_downgrade_free_cancel" class="btn btn_secondary" data-dialog-close="billing_downgrade_free_dialog" commandfor="billing_downgrade_free_dialog" command="close">Cancel</button>
              <button type="button" id="billing_downgrade_free_continue" class="btn btn_delete">Continue</button>
            </div>
          </form>
        </dialog>
        <details id="billing_downgrade_zone" class="billing_downgrade_zone subscription_danger_zone">
          <summary>Danger Zone</summary>
          <div class="subscription_danger_body">
            <h3>Downgrade to Free</h3>
            <p class="help_text" id="billing_downgrade_help">This removes paid plan access immediately.</p>
            <p class="help_text">Type <?php echo settings_index_i18n('PROFILE_BILLING_DOWNGRADE_PHRASE'); ?> to confirm.</p>
            <div class="danger_confirm_pill" id="billing_downgrade_pill">
              <input type="text" id="billing_downgrade_phrase" autocomplete="off" spellcheck="false" autocapitalize="characters" maxlength="24" aria-label="<?php echo settings_index_i18n('PROFILE_BILLING_DOWNGRADE_ARIA'); ?>">
              <button type="button" id="billing_downgrade_confirm" class="btn btn_delete" disabled><?php echo settings_index_i18n('PROFILE_CONFIRM_BUTTON'); ?></button>
            </div>
            <div id="billing_downgrade_status" class="status_text compact_hint" role="status" aria-live="polite"></div>
          </div>
        </details>
      <?php } ?>
    </div>
  </section>
