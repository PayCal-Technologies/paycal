<?php declare(strict_types=1);

namespace PayCal\Domain;

?>

  <section class="panel" id="panel-billing" aria-labelledby="panel-billing-heading" title="<?php echo settings_index_i18n('PROFILE_BILLING_PANEL_HELP'); ?>" data-hover-help="<?php echo settings_index_i18n('PROFILE_BILLING_PANEL_HELP'); ?>" data-billing-hint="<?php echo htmlspecialchars($billingHint, ENT_QUOTES, 'UTF-8'); ?>" data-billing-hydrated="false" data-billing-provider="<?php echo htmlspecialchars($billingProvider, ENT_QUOTES, 'UTF-8'); ?>" data-account-timezone="<?php echo htmlspecialchars((string) ($user->timezone ?? 'UTC'), ENT_QUOTES, 'UTF-8'); ?>">
    <div class="businesses_section_header">
      <div>
        <h2 id="panel-billing-heading"><?php echo settings_index_i18n('PROFILE_BILLING_TITLE'); ?></h2>
      </div>
    </div>
    <div id="billing_status_sr" class="visually_hidden" role="status" aria-live="polite" aria-atomic="true"></div>
    <?php if ($isStripeBilling) { ?>
      <p class="help_text"><?php echo settings_index_i18n('PROFILE_BILLING_STRIPE_NOTE_PREFIX'); ?> <a href="/contact"><?php echo settings_index_i18n('PROFILE_BILLING_STRIPE_NOTE_LINK'); ?></a>.</p>
    <?php } else { ?>
      <p class="help_text"><?php echo settings_index_i18n('PROFILE_BILLING_PUBLIC_CORE_NOTE'); ?></p>
    <?php } ?>

    <div id="billing_free_view" class="billing_shell billing_tier_matrix"<?php if ($hasPaidSubscription) echo ' hidden'; ?>>
      <p class="help_text billing_matrix_intro"><?php echo settings_index_i18n('PROFILE_BILLING_MATRIX_INTRO'); ?></p>
      <div class="billing_tier_grid" role="list" aria-label="<?php echo settings_index_i18n('PROFILE_BILLING_MATRIX_ARIA'); ?>">
        <section class="billing_tier_card billing_tier_card_current" role="listitem" aria-label="<?php echo settings_index_i18n('PROFILE_BILLING_TIER_PUBLIC'); ?>">
          <h3><?php echo settings_index_i18n('PROFILE_BILLING_TIER_PUBLIC'); ?></h3>
          <p class="billing_tier_price"><?php echo settings_index_i18n('PROFILE_BILLING_TIER_PUBLIC_PRICE'); ?></p>
          <ul class="billing_value_list billing_tier_features">
            <li><span><?php echo settings_index_i18n('PROFILE_BILLING_PUBLIC_FEATURE_1'); ?></span></li>
            <li><span><?php echo settings_index_i18n('PROFILE_BILLING_PUBLIC_FEATURE_2'); ?></span></li>
            <li><span><?php echo settings_index_i18n('PROFILE_BILLING_PUBLIC_FEATURE_3'); ?></span></li>
          </ul>
          <p class="billing_tier_current_label"><?php echo settings_index_i18n('PROFILE_BILLING_CURRENT_PLAN'); ?></p>
        </section>

        <section class="billing_tier_card" role="listitem" aria-label="<?php echo settings_index_i18n('PROFILE_BILLING_TIER_PREMIUM'); ?>">
          <h3><?php echo settings_index_i18n('PROFILE_BILLING_TIER_PREMIUM'); ?></h3>
          <p class="billing_tier_price"><?php echo settings_index_i18n('PROFILE_BILLING_TIER_PREMIUM_PRICE'); ?></p>
          <ul class="billing_value_list billing_tier_features">
            <li><span><?php echo settings_index_i18n('PROFILE_BILLING_PREMIUM_FEATURE_5'); ?></span></li>
            <li><span><?php echo settings_index_i18n('PROFILE_BILLING_PREMIUM_FEATURE_6'); ?></span></li>
            <li><span><?php echo settings_index_i18n('PROFILE_BILLING_PREMIUM_REPORTING_FEATURE'); ?></span></li>
          </ul>
          <button type="button" id="billing_upgrade_premium_btn" class="btn btn_secondary" data-billing-plan="premium"><?php echo $isStripeBilling ? settings_index_i18n('PROFILE_BILLING_UPGRADE_PREMIUM_BUTTON') : settings_index_i18n('PROFILE_BILLING_ENABLE_PREMIUM'); ?></button>
          <div id="billing_upgrade_premium_status" class="status_text compact_hint" role="status" aria-live="polite"></div>
        </section>

        <section class="billing_tier_card billing_tier_card_featured" role="listitem" aria-label="<?php echo settings_index_i18n('PROFILE_BILLING_TIER_BUSINESS'); ?>">
          <h3><?php echo settings_index_i18n('PROFILE_BILLING_TIER_BUSINESS'); ?></h3>
          <p class="billing_tier_price"><?php echo settings_index_i18n('PROFILE_BILLING_TIER_BUSINESS_PRICE'); ?></p>
          <ul class="billing_value_list billing_tier_features">
            <li><span><?php echo settings_index_i18n('PROFILE_BILLING_PREMIUM_FEATURE_1'); ?></span></li>
            <li><span><?php echo settings_index_i18n('PROFILE_BILLING_PREMIUM_FEATURE_2'); ?></span></li>
            <li><span><?php echo settings_index_i18n('PROFILE_BILLING_BUSINESS_FEATURE_LISTING'); ?></span></li>
            <li><span><?php echo settings_index_i18n('PROFILE_BILLING_PREMIUM_FEATURE_4'); ?></span></li>
          </ul>
          <button type="button" id="billing_upgrade_business_btn" class="btn btn_primary" data-billing-plan="business"><?php echo $isStripeBilling ? settings_index_i18n('PROFILE_BILLING_UPGRADE_BUSINESS_BUTTON') : settings_index_i18n('PROFILE_BILLING_ENABLE_BUSINESS'); ?></button>
          <div id="billing_upgrade_business_status" class="status_text compact_hint" role="status" aria-live="polite"></div>
        </section>
      </div>
      <p class="billing_businesses_link">
        <a href="#panel-business-connect"><?php echo settings_index_i18n('PROFILE_BILLING_CONNECT_LINK'); ?></a>
      </p>
    </div>

    <div id="billing_premium_view" class="billing_shell"<?php if (!$hasPaidSubscription) echo ' hidden'; ?>>
      <div class="billing_columns">
        <section class="billing_column billing_column_main" aria-label="<?php echo settings_index_i18n('PROFILE_BILLING_PREMIUM_ARIA'); ?>">
          <p class="billing_plan_value">
            <strong id="billing_plan_label"><?php echo $hasActiveBusiness ? settings_index_i18n('PROFILE_BILLING_PLAN_BUSINESS') : settings_index_i18n('PROFILE_BILLING_PLAN_PREMIUM'); ?></strong>
            <span id="billing_plan_status_badge" class="badge" hidden></span>
            <span class="billing_member_since">&mdash; <?php echo settings_index_i18n('PROFILE_BILLING_MEMBER_SINCE'); ?> <span id="billing_start_date">&#8212;</span></span>
          </p>
          <p class="billing_renewal_date" id="billing_renewal_line"><?php echo settings_index_i18n('PROFILE_BILLING_RENEWS'); ?> <span id="billing_renewal_date">&#8212;</span></p>
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
          <button type="button" id="billing_portal_btn" class="btn btn_primary"><?php echo $isStripeBilling ? settings_index_i18n('PROFILE_BILLING_PORTAL_BUTTON') : settings_index_i18n('PROFILE_BILLING_DISABLE_PREMIUM'); ?></button>
          <div id="billing_portal_status" class="status_text compact_hint" role="status" aria-live="polite"></div>
          <p class="billing_businesses_link">
            <a href="/business/"><?php echo settings_index_i18n('PROFILE_BILLING_ORGS_LINK'); ?></a>
          </p>
        </section>

        <section class="billing_column billing_column_side" role="region" aria-label="<?php echo settings_index_i18n('PROFILE_BILLING_PREMIUM_BENEFITS_ARIA'); ?>">
          <h3 id="billing_subscribed_features_title"><?php echo $hasActiveBusiness ? settings_index_i18n('PROFILE_BILLING_BUSINESS_BENEFITS_TITLE') : settings_index_i18n('PROFILE_BILLING_PREMIUM_BENEFITS_TITLE'); ?></h3>
          <ul class="billing_value_list" id="billing_subscribed_features_list" aria-label="<?php echo settings_index_i18n('PROFILE_BILLING_PREMIUM_FEATURES_ARIA'); ?>">
            <?php if ($hasActiveBusiness) { ?>
            <li><span><?php echo settings_index_i18n('PROFILE_BILLING_PREMIUM_FEATURE_1'); ?></span></li>
            <li><span><?php echo settings_index_i18n('PROFILE_BILLING_PREMIUM_FEATURE_2'); ?></span></li>
            <li><span><?php echo settings_index_i18n('PROFILE_BILLING_BUSINESS_FEATURE_LISTING'); ?></span></li>
            <li><span><?php echo settings_index_i18n('PROFILE_BILLING_PREMIUM_FEATURE_4'); ?></span></li>
            <?php } else { ?>
            <li><span><?php echo settings_index_i18n('PROFILE_BILLING_PREMIUM_FEATURE_5'); ?></span></li>
            <li><span><?php echo settings_index_i18n('PROFILE_BILLING_PREMIUM_FEATURE_6'); ?></span></li>
            <li><span><?php echo settings_index_i18n('PROFILE_BILLING_PREMIUM_REPORTING_FEATURE'); ?></span></li>
            <?php } ?>
          </ul>
          <?php if ($hasActivePremium && !$hasActiveBusiness) { ?>
          <div id="billing_business_upgrade_zone" class="billing_business_upgrade_zone">
            <p class="help_text"><?php echo settings_index_i18n('PROFILE_BILLING_UPGRADE_TO_BUSINESS_HELP'); ?></p>
            <button type="button" id="billing_upgrade_business_subscribed_btn" class="btn btn_primary" data-billing-plan="business"><?php echo $isStripeBilling ? settings_index_i18n('PROFILE_BILLING_UPGRADE_BUSINESS_BUTTON') : settings_index_i18n('PROFILE_BILLING_ENABLE_BUSINESS'); ?></button>
            <div id="billing_upgrade_business_subscribed_status" class="status_text compact_hint" role="status" aria-live="polite"></div>
          </div>
          <?php } ?>
        </section>
      </div>
      <?php if ($isStripeBilling) { ?>
        <div id="billing_downgrade_zone" class="billing_downgrade_zone">
          <p class="help_text" id="billing_downgrade_help"><?php echo settings_index_i18n('PROFILE_BILLING_DOWNGRADE_HELP'); ?></p>
          <div class="danger_confirm_pill" id="billing_downgrade_pill">
            <span><?php echo settings_index_i18n('PROFILE_BILLING_DOWNGRADE_PROMPT_PREFIX'); ?> <code><?php echo settings_index_i18n('PROFILE_BILLING_DOWNGRADE_PHRASE'); ?></code></span>
            <input type="text" id="billing_downgrade_phrase" autocomplete="off" spellcheck="false" autocapitalize="characters" maxlength="24" aria-label="<?php echo settings_index_i18n('PROFILE_BILLING_DOWNGRADE_ARIA'); ?>">
            <button type="button" id="billing_downgrade_confirm" class="btn btn_delete" disabled><?php echo settings_index_i18n('PROFILE_CONFIRM_BUTTON'); ?></button>
          </div>
          <div id="billing_downgrade_status" class="status_text compact_hint" role="status" aria-live="polite"></div>
        </div>
      <?php } ?>
    </div>
  </section>
