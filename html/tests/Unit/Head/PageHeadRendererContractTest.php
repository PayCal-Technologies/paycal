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
    $this->assertSame('earnings', PageHeadRenderer::pageFileFor('PAGE_REPORTS'));
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
}
