<?php declare(strict_types=1);

namespace PayCal\Domain;

$contextHeader = BusinessNav::contextHeaderData((string) $currentPage);
$activePage = $contextHeader['currentPage'];
if ($activePage === Page::BUSINESSES->value) {
  $activePage = Page::BUSINESS_DASHBOARD->value;
}

$workspaceBusinessName = '—';
$hasWorkspaceBusinessName = false;
if (isset($workspaceBusiness) && is_array($workspaceBusiness)) {
  $workspaceBusinessName = trim(html_entity_decode((string) ($workspaceBusiness['name'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
  if ($workspaceBusinessName === '') {
    $workspaceBusinessName = '—';
  } else {
    $hasWorkspaceBusinessName = true;
  }
}
?>
<header class="business_context_header" aria-label="<?php echo htmlspecialchars(Strings::i18n('BUSINESS_CONTEXT_HEADER_ARIA'), ENT_QUOTES, 'UTF-8'); ?>">
  <h2 id="business_context_name" class="business_context_name"<?php echo $hasWorkspaceBusinessName ? '' : ' hidden'; ?>><?php echo htmlspecialchars($workspaceBusinessName, ENT_QUOTES, 'UTF-8'); ?></h2>
  <span class="business_context_separator" aria-hidden="true"<?php echo $hasWorkspaceBusinessName ? '' : ' hidden'; ?>></span>
  <nav class="business_subnav" aria-label="<?php echo htmlspecialchars(Strings::i18n('BUSINESS_SUBNAV_ARIA'), ENT_QUOTES, 'UTF-8'); ?>">
    <div class="business_subnav_tabs">
<?php foreach ($contextHeader['tabs'] as $tab) {
  $tabPage = (string) $tab['page'];
  $tabHref = (string) $tab['href'];
  $tabLabel = Strings::i18n((string) $tab['label_key']);
  $isActive = $tabPage === $activePage;
  $ariaCurrent = $isActive ? ' aria-current="page"' : '';
  $activeClass = $isActive ? ' business_subnav_tab--active' : '';
  $minRole = isset($tab['min_role']) && is_string($tab['min_role']) ? $tab['min_role'] : '';
  $minRoleAttr = $minRole !== '' ? ' data-business-tab-min-role="' . htmlspecialchars($minRole, ENT_QUOTES, 'UTF-8') . '"' : '';
?>
      <a href="<?php echo htmlspecialchars($tabHref, ENT_QUOTES, 'UTF-8'); ?>" class="business_subnav_tab<?php echo $activeClass; ?>" data-business-page="<?php echo htmlspecialchars($tabPage, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $minRoleAttr . $ariaCurrent; ?>><?php echo htmlspecialchars($tabLabel, ENT_QUOTES, 'UTF-8'); ?></a>
<?php } ?>
    </div>
  </nav>
<?php if ($activePage === Page::BUSINESS_MEMBERS->value) { ?>
  <div class="business_context_actions">
    <button
      type="button"
      class="business_members_info_button"
      id="business_members_info_button"
      aria-haspopup="dialog"
      aria-controls="business_members_info_dialog"
      aria-label="Open Members guide"
      title="Members guide"
    >
      <span aria-hidden="true">i</span>
    </button>
  </div>
<?php } ?>
</header>
