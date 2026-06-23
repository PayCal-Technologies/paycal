<?php declare(strict_types=1);

namespace PayCal\Domain;

$settingsDashboardCards = [
  ['key' => 'SETTINGS_NAV_ACCESSIBILITY', 'desc' => 'SETTINGS_DASHBOARD_ACCESSIBILITY_DESC', 'href' => '/settings/accessibility/'],
  ['key' => 'SETTINGS_NAV_ACCOUNT', 'desc' => 'SETTINGS_DASHBOARD_ACCOUNT_DESC', 'href' => '/settings/account/'],
  ['key' => 'SETTINGS_NAV_SUBSCRIPTION', 'desc' => 'SETTINGS_DASHBOARD_SUBSCRIPTION_DESC', 'href' => '/settings/subscription/'],
  ['key' => 'SETTINGS_NAV_APPEARANCE', 'desc' => 'SETTINGS_DASHBOARD_APPEARANCE_DESC', 'href' => '/settings/appearance/'],
  ['key' => 'SETTINGS_NAV_CALENDAR', 'desc' => 'SETTINGS_DASHBOARD_CALENDAR_DESC', 'href' => '/settings/calendar/'],
  ['key' => 'SETTINGS_NAV_DATA', 'desc' => 'SETTINGS_DASHBOARD_DATA_DESC', 'href' => '/settings/data/'],
  ['key' => 'SETTINGS_NAV_SECURITY', 'desc' => 'SETTINGS_DASHBOARD_SECURITY_DESC', 'href' => '/settings/security/'],
  ['key' => 'SETTINGS_NAV_DIAGNOSTICS', 'desc' => 'SETTINGS_DASHBOARD_DIAGNOSTICS_DESC', 'href' => '/settings/diagnostics/'],
];
?>
<section class="panel settings_dashboard_panel" aria-labelledby="settings_dashboard_heading">
  <h2 id="settings_dashboard_heading" class="heading-accent settings_card_title"><?php echo settings_index_i18n('SETTINGS_DASHBOARD_TITLE'); ?></h2>
  <p class="help_text"><?php echo settings_index_i18n('SETTINGS_DASHBOARD_INTRO'); ?></p>

  <div class="settings_dashboard_grid">
<?php foreach ($settingsDashboardCards as $card) { ?>
    <article class="settings_dashboard_card">
      <h3><?php echo htmlspecialchars(settings_index_i18n($card['key']), ENT_QUOTES, 'UTF-8'); ?></h3>
      <p><?php echo htmlspecialchars(settings_index_i18n($card['desc']), ENT_QUOTES, 'UTF-8'); ?></p>
      <a class="btn btn_secondary" href="<?php echo htmlspecialchars($card['href'], ENT_QUOTES, 'UTF-8'); ?>">
        <?php echo htmlspecialchars(settings_index_i18n('SETTINGS_DASHBOARD_OPEN_SECTION'), ENT_QUOTES, 'UTF-8'); ?>
      </a>
    </article>
<?php } ?>
  </div>
</section>
