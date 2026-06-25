<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('a11y')]
final class CoreShellA11yContractTest extends TestCase
{
  #[Test]
  public function calendarIndex404UsesDynamicLanguageNotHardcodedEnglish(): void
  {
    $htmlRoot = dirname(__DIR__, 3);
    $indexPage = (string) file_get_contents($htmlRoot . '/index.php');

    $this->assertStringContainsString("defined('USER_LANGUAGE')", $indexPage);
    $this->assertStringContainsString('$notFoundPageLanguageRaw', $indexPage);
    $this->assertStringContainsString('$notFoundPageLanguage', $indexPage);
    $this->assertStringNotContainsString('<html lang="en">', $indexPage);
  }

  #[Test]
  public function calendarDayContextMenuUsesButtonMenuitemsInsidePresentationList(): void
  {
    $htmlRoot = dirname(__DIR__, 3);
    $indexPage = (string) file_get_contents($htmlRoot . '/index.php');

    $this->assertStringContainsString('id="calendar_day_context_menu"', $indexPage);
    $this->assertStringContainsString('role="menu" aria-label="<?php echo htmlspecialchars((string) html_index_i18n(\'CALENDAR_DAY_MENU_ARIA\')', $indexPage);
    $this->assertStringContainsString('<ul role="none"', $indexPage);
    $this->assertStringContainsString('<button type="button" tabindex="-1" data-action="copy" role="menuitem">', $indexPage);
    $this->assertStringNotContainsString('<li tabindex="-1" data-action="copy" role="menuitem">', $indexPage);
  }

  #[Test]
  public function calendarContextMenuJsTargetsButtonMenuitemsNotListItems(): void
  {
    $htmlRoot = dirname(__DIR__, 3);
    $calendarJs = (string) file_get_contents($htmlRoot . '/js/calendar/calendar.js');

    $this->assertStringContainsString('[role="menuitem"][data-action]', $calendarJs);
    $this->assertStringNotContainsString('li[data-action]', $calendarJs);
  }

  #[Test]
  public function verifyPageExposesSkipLinkAndMainLandmark(): void
  {
    $htmlRoot = dirname(__DIR__, 3);
    $verifyPage = (string) file_get_contents($htmlRoot . '/verify/index.php');

    $this->assertStringContainsString('id="skip_to_content" class="skip_link" href="#main"', $verifyPage);
    $this->assertStringContainsString('aria-keyshortcuts="Alt+0"', $verifyPage);
    $this->assertStringContainsString('<main id="main" tabindex="-1"', $verifyPage);
    $this->assertStringContainsString("'SKIP_TO_CONTENT'", $verifyPage);
  }

  #[Test]
  public function unverifiedPageExposesSkipLinkAndMainLandmark(): void
  {
    $htmlRoot = dirname(__DIR__, 3);
    $unverifiedPage = (string) file_get_contents($htmlRoot . '/unverified/index.php');

    $this->assertStringContainsString('id="skip_to_content" class="skip_link" href="#main"', $unverifiedPage);
    $this->assertStringContainsString('aria-keyshortcuts="Alt+0"', $unverifiedPage);
    $this->assertStringContainsString('<main id="main" tabindex="-1"', $unverifiedPage);
    $this->assertStringContainsString("'SKIP_TO_CONTENT'", $unverifiedPage);
  }

  #[Test]
  public function authPageInheritsShellSkipLinkAndMainLandmark(): void
  {
    $htmlRoot = dirname(__DIR__, 3);
    $authPage = (string) file_get_contents($htmlRoot . '/auth/index.php');
    $header = (string) file_get_contents($htmlRoot . '/header.php');

    $this->assertStringContainsString("require_once __DIR__ . '/../header.php'", $authPage);
    $this->assertStringContainsString('id="skip_to_content" class="skip_link" href="#main"', $header);
    $this->assertStringContainsString('<main id="main" role="main" tabindex="-1"', $header);
  }

  #[Test]
  public function headerAndFooterShellAriaLabelsUseI18nKeys(): void
  {
    $htmlRoot = dirname(__DIR__, 3);
    $header = (string) file_get_contents($htmlRoot . '/header.php');
    $footer = (string) file_get_contents($htmlRoot . '/footer.php');

    $this->assertStringContainsString("Strings::headerI18n('DASHBOARD_RESIZE_GRIP_ARIA')", $header);
    $this->assertStringNotContainsString('aria-label="Resize dashboard"', $header);

    $this->assertStringContainsString("'FOOTER_SOC2_BADGE_ARIA'", $footer);
    $this->assertStringContainsString("\$i18n['FOOTER_SOC2_BADGE_ARIA']", $footer);
    $this->assertStringNotContainsString('aria-label="SOC 2 Audit-Ready', $footer);

    $this->assertStringContainsString("'FOOTER_SOCIAL_REDDIT_ARIA'", $footer);
    $this->assertStringContainsString("\$i18n['FOOTER_SOCIAL_REDDIT_ARIA']", $footer);
    $this->assertStringContainsString("'FOOTER_SOCIAL_FACEBOOK_ARIA'", $footer);
    $this->assertStringContainsString("\$i18n['FOOTER_SOCIAL_FACEBOOK_ARIA']", $footer);
    $this->assertStringContainsString("'FOOTER_SOCIAL_LINKEDIN_ARIA'", $footer);
    $this->assertStringContainsString("\$i18n['FOOTER_SOCIAL_LINKEDIN_ARIA']", $footer);
    $this->assertStringContainsString('class="footer_social"', $footer);
    $this->assertStringContainsString('rel="noopener noreferrer"', $footer);
    $this->assertStringContainsString('https://www.reddit.com/r/PayCal', $footer);
    $this->assertStringContainsString('https://www.facebook.com/profile.php?id=61583146649256', $footer);
    $this->assertStringContainsString('https://www.linkedin.com/company/paycaltech/', $footer);
  }

  #[Test]
  public function coreShellAriaKeysAreTranslatedInAllLocales(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $locales = ['de', 'en', 'es', 'fr', 'hi', 'it', 'nl', 'pt', 'tl', 'tr'];
    $requiredKeys = [
      'DASHBOARD_RESIZE_GRIP_ARIA',
      'FOOTER_SOC2_BADGE_ARIA',
      'FOOTER_SOCIAL_ARIA',
      'FOOTER_SOCIAL_REDDIT_ARIA',
      'FOOTER_SOCIAL_FACEBOOK_ARIA',
      'FOOTER_SOCIAL_LINKEDIN_ARIA',
      'SKIP_TO_CONTENT',
    ];

    foreach ($locales as $locale) {
      $stringsFile = $projectRoot . '/strings/' . $locale . '.txt';
      $strings = (string) file_get_contents($stringsFile);

      foreach ($requiredKeys as $key) {
        $this->assertMatchesRegularExpression(
          '/^' . preg_quote($key, '/') . ' .+/m',
          $strings,
          sprintf('Missing or empty %s in %s.txt', $key, $locale),
        );
      }
    }
  }
}
