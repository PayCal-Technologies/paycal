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
  'STATUS',
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
  'FOOTER_SOC2_BADGE_ARIA',
  'FOOTER_SOCIAL_ARIA',
  'FOOTER_SOCIAL_REDDIT_ARIA',
  'FOOTER_SOCIAL_FACEBOOK_ARIA',
  'FOOTER_SOCIAL_LINKEDIN_ARIA',
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
    ['page' => (string) 'PAGE_PRICING',
        'name' => (string) Strings::html('PRICING'),
        'href' => Environment::appURL('pricing/'),
        'arialabel' => 'Pricing',
        'access_key' => (string) '',
        'icon' => (string) ''],
    ['page' => (string) 'PAGE_STATUS',
        'name' => (string) Strings::html('STATUS'),
        'href' => Environment::appURL('status/'),
        'arialabel' => (string) $i18n['STATUS'],
        'access_key' => (string) '',
        'icon' => (string) ''],
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
  if (($currentPage ?? '') === 'PAGE_INDEX') {
    echo Render::jsScript('plaintext-work-capture');
  }
  echo Render::jsScript('business-dek-auto-bootstrap');
}



?>


  </main>

  <footer id="page_footer" class="ledge nav_component nav_component--footer" role="contentinfo" aria-label="<?php echo $i18n['FOOTER']; ?>">
    <nav class="nav_menu nav_menu--secondary" role="navigation" aria-label="<?php echo $i18n['SECONDARY']; ?>">
      <ul aria-label="<?php echo $i18n['PAGES']; ?>">
<?php echo Render::renderNavLinks($navLinks, $currentPage); ?>
      </ul>
    </nav>
    <nav class="footer_social" role="navigation" aria-label="<?php echo htmlspecialchars($i18n['FOOTER_SOCIAL_ARIA'], ENT_QUOTES, 'UTF-8'); ?>">
      <ul>
        <li>
          <a
            class="footer_social_link"
            href="https://www.reddit.com/r/PayCal"
            target="_blank"
            rel="noopener noreferrer"
            aria-label="<?php echo htmlspecialchars($i18n['FOOTER_SOCIAL_REDDIT_ARIA'], ENT_QUOTES, 'UTF-8'); ?>"
          ><svg class="footer_social_icon" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M12 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0zm5.01 4.744c.688 0 1.25.561 1.25 1.249a1.25 1.25 0 0 1-2.498.056l-2.597-.547-.8 3.747c1.824.07 3.48.632 4.674 1.488.308-.309.73-.491 1.207-.491.968 0 1.754.786 1.754 1.754 0 .716-.435 1.333-1.01 1.614a3.111 3.111 0 0 1 .042.52c0 2.694-3.13 4.87-7.004 4.87-3.874 0-7.004-2.176-7.004-4.87 0-.183.015-.366.043-.534A1.748 1.748 0 0 1 4.028 12c0-.968.786-1.754 1.754-1.754.463 0 .898.196 1.207.49 1.207-.883 2.878-1.43 4.744-1.487l.885-4.182a.342.342 0 0 1 .14-.197.35.35 0 0 1 .238-.042l2.906.617a1.214 1.214 0 0 1 1.108-.701zM9.25 12C8.561 12 8 12.562 8 13.25c0 .687.561 1.248 1.25 1.248.687 0 1.248-.561 1.248-1.249 0-.688-.561-1.249-1.249-1.249zm5.5 0c-.687 0-1.248.561-1.248 1.25 0 .687.561 1.248 1.249 1.248.688 0 1.249-.561 1.249-1.249 0-.687-.562-1.249-1.25-1.249zm-5.466 3.99a.327.327 0 0 0-.231.094.33.33 0 0 0 0 .463c.842.842 2.484.913 2.961.913.477 0 2.105-.056 2.961-.913a.361.361 0 0 0 .029-.463.33.33 0 0 0-.464 0c-.547.533-1.684.73-2.512.73-.828 0-1.979-.196-2.512-.73a.326.326 0 0 0-.232-.095z"/></svg></a>
        </li>
        <li>
          <a
            class="footer_social_link"
            href="https://www.facebook.com/profile.php?id=61583146649256"
            target="_blank"
            rel="noopener noreferrer"
            aria-label="<?php echo htmlspecialchars($i18n['FOOTER_SOCIAL_FACEBOOK_ARIA'], ENT_QUOTES, 'UTF-8'); ?>"
          ><svg class="footer_social_icon" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg></a>
        </li>
        <li>
          <a
            class="footer_social_link"
            href="https://www.linkedin.com/company/paycaltech/"
            target="_blank"
            rel="noopener noreferrer"
            aria-label="<?php echo htmlspecialchars($i18n['FOOTER_SOCIAL_LINKEDIN_ARIA'], ENT_QUOTES, 'UTF-8'); ?>"
          ><svg class="footer_social_icon" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg></a>
        </li>
      </ul>
    </nav>
    <div class="footer_soc2_badge_wrap">
      <a
        class="footer_soc2_badge"
        href="/soc2/"
        title="<?php echo htmlspecialchars($i18n['FOOTER_SOC2_BADGE_ARIA'], ENT_QUOTES, 'UTF-8'); ?>"
        aria-label="<?php echo htmlspecialchars($i18n['FOOTER_SOC2_BADGE_ARIA'], ENT_QUOTES, 'UTF-8'); ?>"
      ><svg class="footer_soc2_badge_icon" width="11" height="13" viewBox="0 0 12 14" fill="none" aria-hidden="true" focusable="false"><path d="M6 1 L1 3 V7 C1 10.2 3.4 12.9 6 13.5 C8.6 12.9 11 10.2 11 7 V3 Z" fill="currentColor" fill-opacity="0.18" stroke="currentColor" stroke-width="0.9" stroke-linejoin="round"/><polyline points="3.5,7.5 5.5,9.5 8.5,5.5" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>SOC 2 Audit-Ready
      </a>
    </div>
    <p class="footer_copyright"><?php echo $i18n['FOOTER_TRADEMARK']; ?></p>
  </footer>

  <dialog id="modal_session_timeout" data-dialog-invoker-bridge data-dialog-close-tts="<?php echo htmlspecialchars($i18n['SESSION_TIMEOUT_TITLE'], ENT_QUOTES, 'UTF-8'); ?>" aria-modal="true" aria-labelledby="modal_session_timeout_title" aria-describedby="modal_session_timeout_aria modal_session_timeout_meta">
    <div class="modal_aria visually_hidden">
      <span id="modal_session_timeout_aria"><?php echo $i18n['SESSION_TIMEOUT_MODAL_ARIA']; ?></span>
    </div>
    <div class="modal_meta visually_hidden">
      <span id="modal_session_timeout_meta"><?php echo $i18n['SESSION_TIMEOUT_MODAL_META']; ?></span>
    </div>
    <section class="modal_header centered">
      <button type="button" class="btn btn_close" data-dialog-close="modal_session_timeout" commandfor="modal_session_timeout" command="close" aria-label="<?php echo $i18n['CLOSE']; ?>">&times;</button>
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

if (in_array(Environment::appEnv(), ['dev', 'mac', 'local', 'test'], true)) {
  $userAgent = is_string($_SERVER['HTTP_USER_AGENT'] ?? null) ? $_SERVER['HTTP_USER_AGENT'] : '';
  $acceptLanguage = is_string($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '';
  $ipAddress = Browser::getIp();
  $device = Browser::getDevice();
  $os = Browser::getOs();
  $browser = Browser::getBrowser();
  $appVersion = Environment::appVersion();
  $redisRLine = sprintf(
    "%s:%s (db %s)",
    Environment::redisServer(),
    Environment::redisReadPort(),
    Environment::redisDb()
  );
  $redisWLine = sprintf(
    "%s:%s (db %s)",
    Environment::redisServer(),
    Environment::redisWritePort(),
    Environment::redisDb()
  );

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
}

$lensPageRaw = (string) $currentPage;
$lensPageMap = [
  'PAGE_INDEX' => 'calendar',
  'PAGE_EARNINGS' => 'earnings',
  'PAGE_SITES' => 'sites',
  'PAGE_BUSINESSES' => 'business',
  'PAGE_BUSINESS_DASHBOARD' => 'business',
  'PAGE_BUSINESS_DETAILS' => 'business',
  'PAGE_BUSINESS_MEMBERS' => 'business',
  'PAGE_BUSINESS_SITES' => 'business',
  'PAGE_BUSINESS_PAYROLL' => 'business',
  'PAGE_BUSINESS_AUDIT' => 'business',
  'PAGE_BUSINESS_REPORTS' => 'business',
  'PAGE_REPORTS' => 'reports',
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
