<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PayCal\Domain\Config\Environment;
use PayCal\Domain\Render;
use PHPUnit\Framework\Attributes\Group;

/**
 * RenderTest
 */
#[Group('unit')]
final class RenderTest extends TestCase
{
  private string $tempRoot;

  protected function setUp(): void
  {
    $this->tempRoot = sys_get_temp_dir().'/paycal_render_'.uniqid('', true);
    mkdir($this->tempRoot.'/templates', 0755, true);

    Environment::bootstrap($this->envDefaults([
      'APP_HOME' => $this->tempRoot.'/',
    ]));

    Render::setStrictMode(false);
  }

  protected function tearDown(): void
  {
    Environment::bootstrap($_ENV);
    Render::setStrictMode(false);
    $this->removeDirRecursive($this->tempRoot);
  }

  private function removeDirRecursive(string $path): void
  {
    if (!is_dir($path)) {
      return;
    }

    $iterator = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
      RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $item) {
      if ($item->isDir()) {
        rmdir($item->getPathname());
      } else {
        unlink($item->getPathname());
      }
    }

    rmdir($path);
  }

  /**
   * @return array<string, string>
   */
  private function envDefaults(array $overrides = []): array
  {
    $defaults = [
      'APP_ENV' => 'dev',
      'APP_SCHEME' => 'https',
      'APP_DOMAIN' => 'localhost',
      'APP_HOME' => '/private/var/www/paycal/dev/html/',
      'API_VERSION' => 'v1',
      'REDIS_SERVER' => 'localhost',
      'REDIS_PORT' => '6379',
      'REDIS_READ_PORT' => '6379',
      'REDIS_WRITE_PORT' => '6379',
      'REDIS_DB' => '0',
      'REDIS_USER' => '',
      'REDIS_PASSWORD' => '',
      'REDIS_NEW_SESSION_TTL' => '3600',
      'PC_EMAIL_SMTP_SERVER' => 'localhost',
      'PC_EMAIL_SMTP_PORT' => '25',
      'PC_EMAIL_CONTACT' => 'noreply@example.com',
      'PC_EMAIL_DEBUG' => 'debug@example.com',
      'PC_EMAIL_REPLYTO' => 'reply@example.com',
      'PC_EMAIL_PASSWORD' => 'x',
      'PC_INVITE_CODE' => 'invite',
      'PAYROLL_SIGNING_PRIVATE_KEY' => '',
      'PAYROLL_SIGNING_PUBLIC_KEY' => '',
      'DEV_ALLOW_INLINE_SCRIPTS' => 'true',
      'DEV_SECURITY_DISABLED' => 'false',
      'ENCRYPTION_ENABLED' => 'false',
    ];

    return array_merge($defaults, $overrides);
  }

  private function writeTemplate(string $name, string $contents): void
  {
    file_put_contents($this->tempRoot.'/templates/'.$name.'.php', $contents);
  }

  #[Test]
  public function templateRendersProvidedPlaceholders(): void
  {
    $this->writeTemplate('sample', '<div>__NAME__: __VALUE__</div>');

    $output = Render::template('sample', [
      '__NAME__' => 'Item',
      '__VALUE__' => '42',
    ]);

    $this->assertSame('<div>Item: 42</div>', $output);
  }

  #[Test]
  public function templateMissingPlaceholderThrowsInStrictMode(): void
  {
    $this->writeTemplate('strict-missing', '<div>__REQUIRED__</div>');
    Render::setStrictMode(true);

    $this->expectException(RuntimeException::class);
    Render::template('strict-missing', []);
  }

  #[Test]
  public function templateMissingFileReturnsErrorComment(): void
  {
    $output = Render::template('does-not-exist', []);

    $this->assertSame('<!-- Template does-not-exist not found -->', $output);
  }

  #[Test]
  public function placeholderPatternStillMatchesExpectedTokens(): void
  {
    $template = '<x>__NAME__ __VALUE_2__ __invalid__ __9BAD__</x>';
    preg_match_all('/__([A-Z_][A-Z0-9_]*)__/', $template, $matches);
    $tokens = array_values(array_unique(array_map(static fn(string $m): string => "__{$m}__", $matches[1])));

    $this->assertSame(['__NAME__', '__VALUE_2__'], $tokens);
  }

  #[Test]
  public function sriHashReturnsSha384ForExistingAsset(): void
  {
    $assetDir = $this->tempRoot.'/html/js/signin';
    mkdir($assetDir, 0755, true);
    $assetPath = $assetDir.'/verification-reminder.js';
    $assetBody = "console.log('verification');\n";
    file_put_contents($assetPath, $assetBody);

    $expected = 'sha384-'.base64_encode(hash('sha384', $assetBody, true));
    $actual = Render::sriHash('js/signin/verification-reminder.js');

    $this->assertSame($expected, $actual);
  }

  #[Test]
  public function sriAttributeRendersIntegrityAndCrossoriginWhenHashAvailable(): void
  {
    $assetDir = $this->tempRoot.'/html/js';
    mkdir($assetDir, 0755, true);
    file_put_contents($assetDir.'/calendar.js', "console.log('calendar');\n");

    $attr = Render::sriAttribute('js/calendar.js');
    $this->assertStringContainsString(' integrity="sha384-', $attr);
    $this->assertStringContainsString(' crossorigin="anonymous"', $attr);
  }

  #[Test]
  public function sriAttributeThrowsWhenAssetMissing(): void
  {
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Missing or unreadable static asset for SRI: js/missing.js');
    Render::sriAttribute('js/missing.js');
  }

  #[Test]
  public function buildNavLinksDoesNotContainAdminPage(): void
  {
    $pages = [\PayCal\Domain\Page::INDEX];
    $links = Render::buildNavLinks($pages);

    $pageValues = array_column($links, 'page');
    $this->assertNotContains(\PayCal\Domain\Page::ADMIN->value, $pageValues,
      'Admin nav must not appear in Core buildNavLinks; it is extension-driven via the capability popover.'
    );
  }

  #[Test]
  public function buildNavLinksStandardPagesNeverIncludeAdmin(): void
  {
    $corePages = [
      \PayCal\Domain\Page::INDEX,
      \PayCal\Domain\Page::EARNINGS,
      \PayCal\Domain\Page::SITES,
      \PayCal\Domain\Page::ORGANIZATIONS,
      \PayCal\Domain\Page::PROFILE,
    ];
    $links = Render::buildNavLinks($corePages);
    $pageValues = array_column($links, 'page');

    $this->assertCount(5, $links, 'Exactly five Core nav entries expected.');
    $this->assertNotContains(\PayCal\Domain\Page::ADMIN->value, $pageValues);
  }

  #[Test]
  public function buildNavLinksThrowsWhenCalledWithAdminPage(): void
  {
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessageMatches('/Page::ADMIN must not be passed/');
    Render::buildNavLinks([\PayCal\Domain\Page::ADMIN]);
  }
}
