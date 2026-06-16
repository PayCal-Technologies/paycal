<?php declare(strict_types=1);

namespace PayCal\Domain;

$contextHeader = SettingsNav::contextHeaderData((string) $currentPage);
$activePage = $contextHeader['currentPage'];
?>
<div id="settings-workspace" class="settings_workspace settings_workspace--<?php echo htmlspecialchars($settingsSubpageSlug, ENT_QUOTES, 'UTF-8'); ?>" data-settings-subpage="<?php echo htmlspecialchars($settingsSubpageSlug, ENT_QUOTES, 'UTF-8'); ?>">
  <input type="hidden" id="settings_csrf_token" name="csrf_token" value="<?php echo htmlspecialchars($csrfNonce, ENT_QUOTES, 'UTF-8'); ?>">

  <header class="settings_context_header" aria-label="<?php echo htmlspecialchars(settings_index_i18n('SETTINGS_NAV_ARIA'), ENT_QUOTES, 'UTF-8'); ?>">
    <h2 class="settings_context_title"><?php echo htmlspecialchars(settings_index_i18n('SETTINGS'), ENT_QUOTES, 'UTF-8'); ?></h2>
    <span class="settings_context_separator" aria-hidden="true"></span>
    <nav class="settings_subnav" aria-label="<?php echo htmlspecialchars(settings_index_i18n('SETTINGS_NAV_ARIA'), ENT_QUOTES, 'UTF-8'); ?>">
      <div class="settings_subnav_tabs">
<?php foreach ($contextHeader['tabs'] as $tab) {
  $tabPage = (string) $tab['page'];
  $tabHref = (string) $tab['href'];
  $tabLabel = settings_index_i18n((string) $tab['label_key']);
  $isActive = $tabPage === $activePage;
  $ariaCurrent = $isActive ? ' aria-current="page"' : '';
  $activeClass = $isActive ? ' settings_subnav_tab--active' : '';
?>
        <a href="<?php echo htmlspecialchars($tabHref, ENT_QUOTES, 'UTF-8'); ?>" class="settings_subnav_tab<?php echo $activeClass; ?>" data-settings-page="<?php echo htmlspecialchars($tabPage, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $ariaCurrent; ?>><?php echo htmlspecialchars($tabLabel, ENT_QUOTES, 'UTF-8'); ?></a>
<?php } ?>
      </div>
    </nav>
  </header>

  <div class="settings_page_content">
