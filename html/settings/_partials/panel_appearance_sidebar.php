<?php declare(strict_types=1);

namespace PayCal\Domain;

$navProximityEnabled = $user->isNavProximityEnabled();
$navOverlayMode = $user->isNavOverlayMode();
$navProximityPx = $user->getNavProximityPx();
$overlaySidebarTimeout = $user->getOverlaySidebarTimeoutSeconds();

?>
<section class="panel settings_card_group" id="panel-appearance-sidebar">
  <form id="account_sidebar_form" method="POST" action="<?php echo Environment::appURL('api/v1/settings/style/update/'); ?>" aria-label="<?php echo settings_index_i18n('SETTINGS_APPEARANCE_SIDEBAR_FORM_ARIA'); ?>">
    <input class="visually_hidden" type="text" name="username" value="NOTUSED" autocomplete="username" hidden tabindex="-1" aria-hidden="true">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfNonce; ?>">
    <h2 class="heading-accent settings_card_title"><?php echo settings_index_i18n('SETTINGS_APPEARANCE_SIDEBAR_TITLE'); ?></h2>

    <div class="flex f_baseline w100">
      <label class="w25"><?php echo settings_index_i18n('SETTINGS_STYLE_LABEL_POSITION'); ?></label>
      <div class="w75">
        <div class="radio_group pill_group" data-hover-help="Set primary navigation position on left or right.">
          <input class="radio" type="radio" id="nav_primary_left" name="nav_position_primary" value="left" <?php if ('right' !== (string) ($user->nav_position_primary ?? UserPreferenceDefaults::DEFAULT_NAV_POSITION_PRIMARY)) {
            echo 'checked';
          } ?>>
          <label for="nav_primary_left"><?php echo settings_index_i18n('SETTINGS_POSITION_LEFT'); ?></label>
          <input class="radio" type="radio" id="nav_primary_right" name="nav_position_primary" value="right" <?php if ('right' === ($user->nav_position_primary ?? '')) {
            echo 'checked';
          } ?>>
          <label for="nav_primary_right"><?php echo settings_index_i18n('SETTINGS_POSITION_RIGHT'); ?></label>
        </div>
      </div>
    </div>

    <div class="flex f_baseline w100">
      <label class="w25"><?php echo settings_index_i18n('SETTINGS_NAV_PROXIMITY_LABEL'); ?></label>
      <div class="w75">
        <div class="radio_group pill_group" data-hover-help="<?php echo settings_index_i18n('SETTINGS_NAV_PROXIMITY_HOVER'); ?>">
          <input class="radio" type="radio" id="nav_proximity_off" name="nav_proximity" value="off"<?php if (!$navProximityEnabled) {
            echo ' checked';
          } ?>>
          <label for="nav_proximity_off"><?php echo settings_index_i18n('OFF'); ?></label>
          <input class="radio" type="radio" id="nav_proximity_on" name="nav_proximity" value="on"<?php if ($navProximityEnabled) {
            echo ' checked';
          } ?>>
          <label for="nav_proximity_on"><?php echo settings_index_i18n('ON'); ?></label>
        </div>
      </div>
    </div>

    <div class="flex f_baseline w100">
      <label class="w25"><?php echo settings_index_i18n('SETTINGS_NAV_OVERLAY_LABEL'); ?></label>
      <div class="w75">
        <div class="radio_group pill_group" data-hover-help="<?php echo settings_index_i18n('SETTINGS_NAV_OVERLAY_HOVER'); ?>">
          <input class="radio" type="radio" id="nav_overlay_push" name="nav_overlay" value="push"<?php if (!$navOverlayMode) {
            echo ' checked';
          } ?>>
          <label for="nav_overlay_push"><?php echo settings_index_i18n('OFF'); ?></label>
          <input class="radio" type="radio" id="nav_overlay_overlay" name="nav_overlay" value="overlay"<?php if ($navOverlayMode) {
            echo ' checked';
          } ?>>
          <label for="nav_overlay_overlay"><?php echo settings_index_i18n('ON'); ?></label>
        </div>
      </div>
    </div>

    <div class="flex f_baseline w100" id="nav_proximity_px_row">
      <label class="w25" for="nav_proximity_px"><?php echo settings_index_i18n('SETTINGS_NAV_DISTANCE_LABEL'); ?></label>
      <div class="w75">
        <div class="proximity_slider_wrap" data-hover-help="<?php echo settings_index_i18n('SETTINGS_NAV_DISTANCE_HOVER'); ?>">
          <input
            type="range"
            id="nav_proximity_px"
            name="nav_proximity_px"
            min="0"
            max="600"
            step="10"
            value="<?php echo $navProximityPx; ?>"
            aria-valuemin="0"
            aria-valuemax="600"
            aria-valuenow="<?php echo $navProximityPx; ?>"
            aria-label="<?php echo settings_index_i18n('SETTINGS_NAV_DISTANCE_LABEL'); ?>"
            <?php if (!$navProximityEnabled) {
              echo 'disabled';
            } ?>
          >
          <output for="nav_proximity_px" id="nav_proximity_px_output"><?php echo $navProximityPx; ?> px</output>
        </div>
      </div>
    </div>

    <div class="flex f_baseline w100">
      <label class="w25"><?php echo settings_index_i18n('SETTINGS_OVERLAY_COLLAPSE_LABEL'); ?></label>
      <div class="w75">
        <div class="overlay_collapse_row" id="overlay_sidebar_timeout_row">
          <div class="proximity_slider_wrap" data-hover-help="<?php echo settings_index_i18n('SETTINGS_OVERLAY_COLLAPSE_HOVER'); ?>">
            <input
              type="range"
              id="overlay_sidebar_timeout_seconds"
              name="overlay_sidebar_timeout_seconds"
              min="0"
              max="30"
              step="1"
              value="<?php echo $overlaySidebarTimeout; ?>"
              aria-valuemin="0"
              aria-valuemax="30"
              aria-valuenow="<?php echo $overlaySidebarTimeout; ?>"
              aria-label="<?php echo settings_index_i18n('SETTINGS_OVERLAY_COLLAPSE_LABEL'); ?>"
              <?php if (!$navOverlayMode) {
                echo 'disabled';
              } ?>
            >
            <output for="overlay_sidebar_timeout_seconds" id="overlay_sidebar_timeout_seconds_output"><?php echo $overlaySidebarTimeout; ?>s</output>
          </div>
        </div>
      </div>
    </div>

  </form>
</section>
