<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Observability\Lens;

/**
 * @var string $currentPage
 * @var float  $startTime
 */
$i18n = [];
$i18nKeys = [
  'ABOUT_US',
  'BLOG',
  'HELP',
  'MEDIA',
  'CONTACT',
  'GITHUB',
  'TRANSPARENCY',
  'POLICIES',
  'FOOTER',
  'SECONDARY',
  'PAGES',
  'FOOTER_COPYRIGHT',
  'FOOTER_TRADEMARK',
  'SESSION_TIMEOUT_MODAL_ARIA',
  'SESSION_TIMEOUT_MODAL_META',
  'CLOSE',
  'SESSION_TIMEOUT_TITLE',
  'SESSION_TIMEOUT_COUNTDOWN_PREFIX',
  'SESSION_TIMEOUT_COUNTDOWN_SUFFIX',
  'SESSION_TIMEOUT_STAY_LOGGED_IN',
];
foreach ($i18nKeys as $key) {
  $i18n[$key] = Strings::i18n($key);
}

$navLinks = [
    ['page' => (string) 'PAGE_ABOUT',
        'name' => (string) Strings::html('ABOUT_US'),
        'href' => 'https://paycaltech.com/about/',
        'arialabel' => (string) $i18n['ABOUT_US'],
        'access_key' => (string) 'A', 'icon' => '',
        'extra_attrs' => "target='_blank' rel='noopener noreferrer'"],
    ['page' => (string) 'PAGE_CONTACT',
        'name' => (string) Strings::html('CONTACT_HTML'),
        'href' => Environment::appURL('contact/'),
        'arialabel' => (string) $i18n['CONTACT'],
        'access_key' => (string) 'n',
        'icon' => (string) ''],
    ['page' => (string) 'PAGE_GITHUB',
        'name' => (string) Strings::html('GITHUB_HTML'),
        'href' => 'https://github.com/PayCal-Technologies/paycal',
        'arialabel' => (string) $i18n['GITHUB'],
        'access_key' => (string) 'g',
        'icon' => (string) '',
        'extra_attrs' => "target='_blank' rel='noopener noreferrer'"],
    ['page' => (string) 'PAGE_HELP',
        'name' => (string) Strings::html('HELP'),
        'href' => Environment::appURL('help/'),
        'arialabel' => (string) $i18n['HELP'],
        'access_key' => (string) 'h',
        'icon' => (string) ''],
    ['page' => (string) 'PAGE_MEDIA',
      'name' => (string) Strings::html('MEDIA_HTML'),
      'href' => Environment::appURL('media/'),
      'arialabel' => (string) $i18n['MEDIA'],
      'access_key' => (string) 'm',
      'icon' => (string) ''],
    ['page' => (string) 'PAGE_POLICIES',
        'name' => (string) Strings::html('POLICIES_HTML'),
        'href' => 'https://paycaltech.com/policies/',
        'arialabel' => (string) $i18n['POLICIES'],
        'access_key' => (string) 'l',
        'icon' => (string) '',
        'extra_attrs' => "target='_blank' rel='noopener noreferrer'"],
    ['page' => (string) 'PAGE_TRANSPARENCY',
        'name' => (string) Strings::html('TRANSPARENCY_HTML'),
        'href' => 'https://paycaltech.com/transparency/',
        'arialabel' => (string) $i18n['TRANSPARENCY'],
        'access_key' => (string) 't',
        'icon' => (string) '',
        'extra_attrs' => "target='_blank' rel='noopener noreferrer'"],
];

$hash = Authentication::getSessionHashFromCookie();
$isAuthenticated = $hash !== null && Authentication::sessionExists($hash);

if ($isAuthenticated) {
  echo Render::jsScript('-');
  echo Render::jsScript('encryption');
  echo Render::jsScript('org-dek-auto-bootstrap');
}



?>


  </main>

  <footer id="page_footer" class="ledge nav_component nav_component--footer" role="contentinfo" aria-label="<?php echo $i18n['FOOTER']; ?>">
    <nav class="nav_menu nav_menu--secondary" role="navigation" aria-label="<?php echo $i18n['SECONDARY']; ?>">
      <ul aria-label="<?php echo $i18n['PAGES']; ?>">
<?php echo Render::renderNavLinks($navLinks, $currentPage); ?>
      </ul>
    </nav>
    <div class="footer_soc2_badge_wrap">
      <a
        class="footer_soc2_badge"
        href="/soc2/"
        title="SOC 2 Audit-Ready — view compliance details"
        aria-label="SOC 2 Audit-Ready — view compliance details"
      ><svg class="footer_soc2_badge_icon" width="11" height="13" viewBox="0 0 12 14" fill="none" aria-hidden="true" focusable="false"><path d="M6 1 L1 3 V7 C1 10.2 3.4 12.9 6 13.5 C8.6 12.9 11 10.2 11 7 V3 Z" fill="currentColor" fill-opacity="0.18" stroke="currentColor" stroke-width="0.9" stroke-linejoin="round"/><polyline points="3.5,7.5 5.5,9.5 8.5,5.5" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>SOC 2 Audit-Ready
      </a>
    </div>
    <p class="footer_copyright"><?php echo $i18n['FOOTER_TRADEMARK']; ?></p>
  </footer>

  <dialog id="modal_session_timeout" aria-labelledby="modal_session_timeout_title" aria-describedby="modal_session_timeout_aria modal_session_timeout_meta">
    <div class="modal_aria visually_hidden">
      <span id="modal_session_timeout_aria"><?php echo $i18n['SESSION_TIMEOUT_MODAL_ARIA']; ?></span>
    </div>
    <div class="modal_meta visually_hidden">
      <span id="modal_session_timeout_meta"><?php echo $i18n['SESSION_TIMEOUT_MODAL_META']; ?></span>
    </div>
    <section class="modal_header centered">
      <button type="button" class="btn btn_close" data-dialog-close="modal_session_timeout" aria-label="<?php echo $i18n['CLOSE']; ?>">&times;</button>
      <h1 id="modal_session_timeout_title" class="modal_title"><?php echo $i18n['SESSION_TIMEOUT_TITLE']; ?></h1>
    </section>
    <section class="modal_content centered">
      <p><?php echo $i18n['SESSION_TIMEOUT_COUNTDOWN_PREFIX']; ?> <span id="session_timeout_countdown">60</span> <?php echo $i18n['SESSION_TIMEOUT_COUNTDOWN_SUFFIX']; ?></p>
    </section>
    <section class="modal_footer">
      <div class="modal_controls centered">
        <button id="session_extend_btn"><?php echo $i18n['SESSION_TIMEOUT_STAY_LOGGED_IN']; ?></button>
      </div>
    </section>
  </dialog>

<?php if (Authentication::validateAndTouchSession()) { ?>
  <!--   <script src='/pwa/' defer nonce='<?php echo User::nonce(); ?>'></script> -->

<?php }

$backendLoadMs = max(0.0, ($startTime + microtime(true)) * 1000);
$timeTaken = sprintf('%.2fms', $backendLoadMs);
$memoryUsageBytes = memory_get_usage();
$memoryUsageMB = $memoryUsageBytes / (1024 * 1024);
$memoryUsageGB = $memoryUsageMB / 1024;

if ($memoryUsageMB < 1024) {
  // If less than 1024 MB, show in MB
  $formattedMemoryUsage = Strings::formatLocalizedNumber($memoryUsageMB, 2, 2).' MB';
} else {
  // If 1024 MB or more, show in GB
  $formattedMemoryUsage = Strings::formatLocalizedNumber($memoryUsageGB, 2, 2).' GB';
}

$peakMemoryBytes = memory_get_peak_usage();
$peakMemoryMB = $peakMemoryBytes / (1024 * 1024);
$formattedPeakMemoryUsage = Strings::formatLocalizedNumber($peakMemoryMB, 2, 2).' MB';

$userAgent = is_string($_SERVER['HTTP_USER_AGENT'] ?? null) ? $_SERVER['HTTP_USER_AGENT'] : '';
$acceptLanguage = is_string($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '';
$ipAddress = Browser::getIp();
$device = Browser::getDevice();
$os = Browser::getOs();
$browser = Browser::getBrowser();
$appVersion = Environment::appVersion();
$redisRLine = Environment::appEnv() !== "prod"
  ? sprintf(
      "%s:%s (db %s)",
      Environment::redisServer(),
      Environment::redisReadPort(),
      Environment::redisDb()
    )
  : "";

$redisWLine = Environment::appEnv() !== "prod"
  ? sprintf(
      "%s:%s (db %s)",
      Environment::redisServer(),
      Environment::redisWritePort(),
      Environment::redisDb()
    )
  : "";

echo <<<HTML
	<!--
	  Render   : {$timeTaken}
	  Memory   : {$formattedMemoryUsage}
	  Version  : {$appVersion}
	  RedisR   : {$redisRLine}
	  RedisW   : {$redisWLine}
	  Device   : {$device}
	  IP       : {$ipAddress}
	  OS       : {$os}
	  Browser  : {$browser}
	  Agent    : {$userAgent}
	  Language : {$acceptLanguage}
	-->


HTML;

$lensPageRaw = (string) $currentPage;
$lensPageMap = [
  'PAGE_INDEX' => 'calendar',
  'PAGE_EARNINGS' => 'earnings',
  'PAGE_SITES' => 'sites',
  'PAGE_ORGANIZATIONS' => 'organizations',
  'PAGE_SETTINGS' => 'settings',
  'PAGE_ADMIN' => 'admin',
  'PAGE_HELP' => 'help',
  'PAGE_MEDIA' => 'media',
  'PAGE_TRANSPARENCY' => 'transparency',
  'PAGE_PAYPERIODS' => 'payperiods',
  'PAGE_ABOUT' => 'about',
  'PAGE_BLOG' => 'blog',
  'PAGE_CONTACT' => 'contact',
  'PAGE_FAQ' => 'faq',
  'PAGE_POLICIES' => 'policies',
  'PAGE_TESTS' => 'tests',
];
$lensPage = $lensPageMap[$lensPageRaw] ?? strtolower(str_replace('PAGE_', '', $lensPageRaw));
$lensLoad = (string) $timeTaken;
$lensMemory = (string) $formattedMemoryUsage;
$lensPeakMemory = (string) $formattedPeakMemoryUsage;

echo '<!-- LENS DASHBOARD METRICS SEGMENT (footer-emitted, CSP-safe) -->';
echo '<section id="lens_footer_segment" class="visually_hidden" aria-hidden="true">';
echo '<div id="lens_server_metrics" '
  .'data-source="footer" '
  .'data-page="'.htmlspecialchars($lensPage, ENT_QUOTES, 'UTF-8').'" '
  .'data-php-load-time="'.htmlspecialchars($lensLoad, ENT_QUOTES, 'UTF-8').'" '
  .'data-memory-usage="'.htmlspecialchars($lensMemory, ENT_QUOTES, 'UTF-8').'" '
  .'data-peak-memory-usage="'.htmlspecialchars($lensPeakMemory, ENT_QUOTES, 'UTF-8').'"'
  .'></div>';
echo '</section>';
echo '<!-- /LENS DASHBOARD METRICS SEGMENT -->';

// Render Lens observability (DEV-only)
Lens::render();

?>

</body>
</html>
<?php
if (ContentView::isDocPage($currentPage)) {
  ContentView::process($currentPage, $cspNonce ?? '', $pageTitle ?? '');
}
?>
