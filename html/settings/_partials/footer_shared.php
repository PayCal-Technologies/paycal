<?php declare(strict_types=1);

namespace PayCal\Domain;

?>
  </div><!-- settings_page_content -->
</div><!-- settings-workspace -->

<?php
echo PHP_EOL . '<link rel="stylesheet" href="' . Render::cssURL('settings') . '">' . PHP_EOL;

if (($settingsSubpageSlug ?? '') === 'account') {
  echo PHP_EOL . '<link rel="stylesheet" href="' . Render::cssURL('profile') . '">' . PHP_EOL;
}

echo PHP_EOL . Render::jsScript('settings') . PHP_EOL;

if (($settingsSubpageSlug ?? '') === 'account') {
  echo PHP_EOL . Render::jsScript('business-profile') . PHP_EOL;
}
