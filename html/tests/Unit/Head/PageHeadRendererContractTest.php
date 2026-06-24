<?php declare(strict_types=1);

use PayCal\Domain\PageHeadRenderer;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('contract')]
final class PageHeadRendererContractTest extends TestCase
{
  #[Test]
  public function robotsMetaDoesNotDuplicateBotSpecificTags(): void
  {
    $html = PageHeadRenderer::renderRobotsMeta();

    $this->assertStringContainsString('name="robots"', $html);
    $this->assertStringNotContainsString('googlebot', $html);
    $this->assertStringNotContainsString('bingbot', $html);
  }

  #[Test]
  public function pageFileMapIncludesBusinessSubPagesAndReports(): void
  {
    $this->assertSame('business', PageHeadRenderer::pageFileFor('PAGE_BUSINESS_DETAILS'));
    $this->assertSame('business', PageHeadRenderer::pageFileFor('PAGE_BUSINESS_DASHBOARD'));
    $this->assertSame('business', PageHeadRenderer::pageFileFor('PAGE_BUSINESS_REPORTS'));
    $this->assertSame('business', PageHeadRenderer::pageFileFor('PAGE_CONNECTIONS'));
    $this->assertSame('earnings', PageHeadRenderer::pageFileFor('PAGE_REPORTS'));
    $this->assertSame('settings', PageHeadRenderer::pageFileFor('PAGE_SETTINGS_SUBSCRIPTION'));
    $this->assertSame('pricing', PageHeadRenderer::pageFileFor('PAGE_PRICING'));
    $this->assertSame('transparency', PageHeadRenderer::pageFileFor('PAGE_STATUS'));
    $this->assertSame('content', PageHeadRenderer::pageFileFor('PAGE_UNKNOWN'));
  }

  #[Test]
  public function identityMetaDoesNotEmitRedundantKeywordTags(): void
  {
    $html = PageHeadRenderer::renderIdentityMeta([
      'pageLanguage' => 'en-CA',
      'metaDescription' => 'PayCal business workspace',
      'metaDescriptionLong' => 'Long description',
      'pageTitle' => 'Business',
    ]);

    $this->assertStringContainsString('name="description"', $html);
    $this->assertStringNotContainsString('name="keywords"', $html);
    $this->assertStringNotContainsString('page-subject', $html);
    $this->assertSame(1, substr_count($html, 'name="application-name"'));
  }

  #[Test]
  public function speculationRulesPrefetchDocumentsWithoutPrerendering(): void
  {
    $html = PageHeadRenderer::renderSpeculationRules('test-nonce');

    $this->assertStringContainsString('<script type="speculationrules" nonce="test-nonce">', $html);
    $this->assertStringContainsString('"prefetch"', $html);
    $this->assertStringContainsString('"source":"document"', $html);
    $this->assertStringContainsString('"eagerness":"moderate"', $html);
    $this->assertStringContainsString('"tag":"paycal-document-prefetch"', $html);
    $this->assertStringContainsString('"href_matches":"/api/*"', $html);
    $this->assertStringContainsString('"href_matches":"/signout*"', $html);
    $this->assertStringContainsString('"selector_matches":"[download], [target], [rel~=nofollow], [data-no-speculation]"', $html);
    $this->assertStringNotContainsString('"prerender"', $html);
  }

  #[Test]
  public function shellAllowsSpeculationRulesWithoutUnsafeInlineScripts(): void
  {
    $projectRoot = dirname(__DIR__, 3);
    $header = (string) file_get_contents($projectRoot . '/header.php');
    $layout = (string) file_get_contents($projectRoot . '/src/Domain/Layout.php');

    $this->assertStringContainsString("'inline-speculation-rules'", $header);
    $this->assertStringContainsString("'inline-speculation-rules'", $layout);
    $this->assertStringContainsString('data-no-speculation', $header);
  }
}
