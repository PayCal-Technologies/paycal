<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('a11y')]
final class SitesCspStyleContractTest extends TestCase
{
  #[Test]
  public function sitesTemplateDoesNotUseInlineStyleAttributes(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $page = (string) file_get_contents($projectRoot . '/html/sites/index.php');

    $this->assertStringNotContainsString('style="', $page);
    $this->assertStringNotContainsString("style='", $page);
  }

  #[Test]
  public function sitesTemplateUsesCspSafeSkeletonClasses(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $page = (string) file_get_contents($projectRoot . '/html/sites/index.php');

    $this->assertStringContainsString('sites_earnings_skeleton_wrap', $page);
    $this->assertStringContainsString('sites_earnings_skeleton_bars', $page);
    $this->assertStringContainsString('sites_sk_bar_h_', $page);
  }
}
