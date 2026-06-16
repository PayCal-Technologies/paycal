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

$cspNonce = $_SERVER['CSP_NONCE'] ?? User::nonce();
$legacyHashRedirects = SettingsNav::legacyHashRedirects();
echo PHP_EOL . '<script type="application/json" id="settings-legacy-hash-redirects" nonce="' . htmlspecialchars($cspNonce, ENT_QUOTES, 'UTF-8') . '">'
  . json_encode($legacyHashRedirects, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
  . '</script>' . PHP_EOL;

echo PHP_EOL . Render::jsScript('settings') . PHP_EOL;

if (($settingsSubpageSlug ?? '') === 'account') {
  echo PHP_EOL . Render::jsScript('business-profile') . PHP_EOL;
}
