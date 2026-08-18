<?php declare(strict_types=1);

use PayCal\Domain\Config\Environment;
use PayCal\Domain\CrawlPolicy;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('contract')]
final class RobotsTxtContractTest extends TestCase
{
  /**
   * @return array<string, string>
   */
  private function envDefaults(array $overrides = []): array
  {
    $defaults = [
      'APP_ENV' => 'prod',
      'APP_SCHEME' => 'https',
      'APP_DOMAIN' => 'paycal.app',
      'APP_HOME' => '/private/var/www/paycal-technologies/paycal-private/html/',
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
      'DEV_ALLOW_INLINE_SCRIPTS' => 'false',
      'DEV_SECURITY_DISABLED' => 'false',
      'ENCRYPTION_ENABLED' => 'false',
    ];

    return array_merge($defaults, $overrides);
  }

  protected function tearDown(): void
  {
    Environment::bootstrap($_ENV);
    unset($_SERVER['HTTP_HOST']);
  }

  #[Test]
  public function productionPaycalAppRobotsTxtDisallowsPrivateAppSurfaces(): void
  {
    Environment::bootstrap($this->envDefaults([
      'APP_ENV' => 'prod',
      'APP_DOMAIN' => 'paycal.app',
    ]));
    $robots = CrawlPolicy::renderRobotsTxt();

    foreach (CrawlPolicy::PRIVATE_DISALLOW_PATHS as $path) {
      $this->assertStringContainsString('Disallow: ' . $path, $robots, $path);
    }

    foreach (CrawlPolicy::QUERY_TRAP_DISALLOW_PATTERNS as $pattern) {
      $this->assertStringContainsString('Disallow: ' . $pattern, $robots, $pattern);
    }

    $this->assertStringContainsString('Sitemap: https://paycal.app/sitemap.xml', $robots);
    $this->assertStringContainsString('Disallow is not security', $robots);
    $this->assertDoesNotMatchRegularExpression(
      '/User-agent: \*\nDisallow: \/\n/',
      $robots,
      'General crawlers must not have a blanket Disallow: /',
    );
  }

  #[Test]
  public function productionRobotsTxtDoesNotBlockPublicMarketingAssets(): void
  {
    Environment::bootstrap($this->envDefaults(['APP_ENV' => 'prod', 'APP_DOMAIN' => 'paycal.app']));
    $robots = CrawlPolicy::renderRobotsTxt();

    $this->assertStringNotContainsString("Disallow: /css/\n", $robots);
    $this->assertDoesNotMatchRegularExpression('/^Disallow: \/js\/$/m', $robots);
    $this->assertStringNotContainsString("Disallow: /fonts/\n", $robots);
    $this->assertStringNotContainsString("Disallow: /img/\n", $robots);
    $this->assertStringNotContainsString("Disallow: /about/\n", $robots);
    $this->assertStringNotContainsString("Disallow: /help/\n", $robots);
    $this->assertStringNotContainsString("Disallow: /premium/\n", $robots);
    $this->assertStringNotContainsString("Disallow: /auth/\n", $robots);
  }

  #[Test]
  public function productionRobotsTxtBlocksAiTrainingCrawlers(): void
  {
    Environment::bootstrap($this->envDefaults(['APP_ENV' => 'prod', 'APP_DOMAIN' => 'paycal.app']));
    $robots = CrawlPolicy::renderRobotsTxt();

    $this->assertStringContainsString('User-agent: GPTBot', $robots);
    $this->assertStringContainsString('User-agent: Google-Extended', $robots);
    $this->assertStringContainsString('User-agent: ClaudeBot', $robots);
    $this->assertMatchesRegularExpression('/User-agent: GPTBot\nDisallow: \//', $robots);
  }

  #[Test]
  public function devPaycalAppDomainRobotsTxtBlocksAllCrawlers(): void
  {
    Environment::bootstrap($this->envDefaults([
      'APP_ENV' => 'dev',
      'APP_DOMAIN' => 'dev.paycal.app',
    ]));
    $robots = CrawlPolicy::renderRobotsTxt();

    $this->assertStringContainsString('Non-production hosts (dev.paycal.app)', $robots);
    $this->assertStringContainsString('User-agent: *', $robots);
    $this->assertStringContainsString('Disallow: /', $robots);
    $this->assertStringNotContainsString('Sitemap:', $robots);
  }

  #[Test]
  public function macPaycalAppDomainRobotsTxtBlocksAllCrawlers(): void
  {
    Environment::bootstrap($this->envDefaults([
      'APP_ENV' => 'mac',
      'APP_DOMAIN' => 'mac.paycal.app',
    ]));
    $robots = CrawlPolicy::renderRobotsTxt();

    $this->assertStringContainsString('Non-production hosts (mac.paycal.app)', $robots);
    $this->assertStringContainsString('Disallow: /', $robots);
    $this->assertStringNotContainsString('Sitemap:', $robots);
  }

  #[Test]
  public function macPaycalLocalDomainRobotsTxtBlocksAllCrawlers(): void
  {
    Environment::bootstrap($this->envDefaults([
      'APP_ENV' => 'mac',
      'APP_DOMAIN' => 'mac.paycal.local',
    ]));
    $robots = CrawlPolicy::renderRobotsTxt();

    $this->assertStringContainsString('Disallow: /', $robots);
    $this->assertStringNotContainsString('Sitemap:', $robots);
  }

  #[Test]
  public function misconfiguredProdEnvOnDevDomainStillBlocksIndexing(): void
  {
    Environment::bootstrap($this->envDefaults([
      'APP_ENV' => 'prod',
      'APP_DOMAIN' => 'dev.paycal.app',
    ]));
    $robots = CrawlPolicy::renderRobotsTxt();

    $this->assertStringContainsString('Disallow: /', $robots);
    $this->assertStringNotContainsString('Sitemap:', $robots);
    $this->assertFalse(Environment::allowsPublicIndexing());
  }

  #[Test]
  public function nonProductionAppEnvRobotsTxtBlocksAllCrawlers(): void
  {
    foreach (['mac', 'dev', 'local', 'test'] as $env) {
      Environment::bootstrap($this->envDefaults([
        'APP_ENV' => $env,
        'APP_DOMAIN' => 'paycal.app',
      ]));
      $robots = CrawlPolicy::renderRobotsTxt();

      $this->assertStringContainsString('Disallow: /', $robots, $env);
      $this->assertStringNotContainsString('Sitemap:', $robots, $env);
    }
  }

  #[Test]
  public function productionSitemapListsPublicMarketingPaths(): void
  {
    Environment::bootstrap($this->envDefaults(['APP_ENV' => 'prod', 'APP_DOMAIN' => 'paycal.app']));
    $sitemap = CrawlPolicy::renderSitemapXml();

    $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $sitemap);
    $this->assertStringContainsString('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', $sitemap);

    foreach (CrawlPolicy::PUBLIC_INDEX_PATHS as $path) {
      $canonical = Environment::publicMetadataURL($path);
      $this->assertStringContainsString('<loc>' . $canonical . '</loc>', $sitemap, $path);
    }

    $this->assertStringContainsString('<loc>https://paycal.app/premium/</loc>', $sitemap);
  }

  #[Test]
  public function nonProductionSitemapIsEmpty(): void
  {
    Environment::bootstrap($this->envDefaults([
      'APP_ENV' => 'dev',
      'APP_DOMAIN' => 'dev.paycal.app',
    ]));
    $this->assertSame('', CrawlPolicy::renderSitemapXml());
  }

  #[Test]
  public function authenticatedPagesShouldNotIndexEvenOnProduction(): void
  {
    Environment::bootstrap($this->envDefaults(['APP_ENV' => 'prod', 'APP_DOMAIN' => 'paycal.app']));

    $this->assertFalse(CrawlPolicy::shouldIndexPage('PAGE_SETTINGS', true));
    $this->assertFalse(CrawlPolicy::shouldIndexPage('PAGE_INDEX', true));
    $this->assertFalse(CrawlPolicy::shouldIndexPage('PAGE_HELP', true));
  }

  #[Test]
  public function publicMarketingPagesShouldIndexWhenUnauthenticatedOnProduction(): void
  {
    Environment::bootstrap($this->envDefaults(['APP_ENV' => 'prod', 'APP_DOMAIN' => 'paycal.app']));

    $this->assertTrue(CrawlPolicy::shouldIndexPage('PAGE_HELP', false));
    $this->assertTrue(CrawlPolicy::shouldIndexPage('PAGE_PREMIUM', false));
    $this->assertFalse(CrawlPolicy::shouldIndexPage('PAGE_SETTINGS', false));
  }

  #[Test]
  public function nginxSeoEndpointsSnippetRoutesDynamicHandlers(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $nginxConf = (string) file_get_contents($projectRoot . '/docs/nginx/seo-endpoints.conf');
    $nativeConf = (string) file_get_contents($projectRoot . '/docs/nginx/paycal-native.conf');

    $this->assertStringContainsString('location = /robots.txt', $nginxConf);
    $this->assertStringContainsString('fastcgi_pass php', $nginxConf);
    $this->assertStringContainsString('fastcgi_param SCRIPT_FILENAME $document_root/robots.php', $nginxConf);
    $this->assertStringContainsString('fastcgi_param SCRIPT_NAME /robots.php', $nginxConf);

    $this->assertStringContainsString('location = /sitemap.xml', $nginxConf);
    $this->assertStringContainsString('fastcgi_param SCRIPT_FILENAME $document_root/sitemap.php', $nginxConf);
    $this->assertStringContainsString('fastcgi_param SCRIPT_NAME /sitemap.php', $nginxConf);

    $this->assertStringContainsString('include snippets/paycal-seo-endpoints.conf', $nativeConf);
  }
}
