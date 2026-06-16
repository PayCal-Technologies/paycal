<?php declare(strict_types=1);

namespace PayCal\Domain;

$contextHeader = BusinessSurface::contextHeaderData((string) $currentPage);
$activePage = $contextHeader['currentPage'];
if ($activePage === 'PAGE_BUSINESSES') {
  $activePage = 'PAGE_BUSINESS_DASHBOARD';
}
$workspaceBusinessName = trim(html_entity_decode((string) ($workspaceBusinessName ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
$hasWorkspaceBusinessName = $workspaceBusinessName !== '' && $workspaceBusinessName !== '—';
if ($workspaceBusinessName === '') {
  $workspaceBusinessName = '—';
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
?>
      <a href="<?php echo htmlspecialchars($tabHref, ENT_QUOTES, 'UTF-8'); ?>" class="business_subnav_tab<?php echo $activeClass; ?>" data-business-page="<?php echo htmlspecialchars($tabPage, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $ariaCurrent; ?>><?php echo htmlspecialchars($tabLabel, ENT_QUOTES, 'UTF-8'); ?></a>
<?php } ?>
    </div>
  </nav>
</header>
