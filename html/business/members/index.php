<?php declare(strict_types=1);

namespace PayCal\Domain;

$currentPage = 'PAGE_BUSINESS_MEMBERS';

require_once dirname(__DIR__) . '/_layout.php';
?>

<div id="business-workspace" class="business_workspace business_members" data-business-subpage="members">

  <h1 class="visually_hidden"><?php echo htmlspecialchars(Strings::i18n('BUSINESS_NAV_MEMBERS'), ENT_QUOTES, 'UTF-8'); ?></h1>

  <p class="business_public_preview_lead"><?php echo htmlspecialchars(Strings::i18n('BUSINESS_PUBLIC_PREVIEW_MEMBERS'), ENT_QUOTES, 'UTF-8'); ?></p>

</div>

<?php
require __DIR__ . '/../_partials/extension_disclaimer.php';
require_once \PayCal\Domain\Config\Environment::appHome() . 'html/footer.php';
