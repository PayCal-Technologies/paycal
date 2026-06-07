<?php declare(strict_types=1);

use PayCal\Domain\OrganizationSiteLinkResolver;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class EarningsSiteResolutionDiagnosticsContractTest extends TestCase
{
  #[Test]
  public function resolverReturnsOwnerAndSiteMatchWhenExactRefExists(): void
  {
    $context = [
      'ref_set' => ['U123:S001' => true],
      'id_refs' => ['S001' => ['U123:S001']],
      'normalized_name_set' => [],
    ];

    $strategy = OrganizationSiteLinkResolver::resolveMatchStrategy($context, 'S001', 'U123', 'Example Site');
    $this->assertSame('owner_and_site', $strategy);
  }

  #[Test]
  public function resolverFallsBackToUniqueSiteIdWhenOwnerDoesNotMatch(): void
  {
    $context = [
      'ref_set' => ['U999:S002' => true],
      'id_refs' => ['S002' => ['U999:S002']],
      'normalized_name_set' => [],
    ];

    $strategy = OrganizationSiteLinkResolver::resolveMatchStrategy($context, 'S002', 'U123', 'Other Site');
    $this->assertSame('unique_site_id', $strategy);
  }

  #[Test]
  public function resolverFallsBackToNormalizedSiteNameWhenIdCannotResolve(): void
  {
    $context = [
      'ref_set' => [],
      'id_refs' => ['S003' => ['U111:S003', 'U222:S003']],
      'normalized_name_set' => ['edmontonindustrialconsultantshq' => true],
    ];

    $strategy = OrganizationSiteLinkResolver::resolveMatchStrategy($context, 'S003', 'U555', 'Edmonton Industrial Consultants HQ');
    $this->assertSame('site_name', $strategy);
  }

  #[Test]
  public function resolverReturnsNoMatchWhenNoStrategyApplies(): void
  {
    $context = [
      'ref_set' => [],
      'id_refs' => [],
      'normalized_name_set' => [],
    ];

    $strategy = OrganizationSiteLinkResolver::resolveMatchStrategy($context, 'S404', 'U404', 'Unknown');
    $this->assertSame('no_match', $strategy);
  }

  #[Test]
  public function resolverNormalizesSiteNamesConservatively(): void
  {
    $normalized = OrganizationSiteLinkResolver::normalizeSiteName('  EIC-HQ / North  ');
    $this->assertSame('eichqnorth', $normalized);
  }

  #[Test]
  public function earningsPageContainsFallbackWarningLensSignalContract(): void
  {
    $projectRoot = dirname(__DIR__, 3);
    $page = (string) file_get_contents($projectRoot . '/html/earnings/index.php');

    $this->assertStringContainsString('earnings.team.site_resolution.fallback_ratio_warn', $page);
    $this->assertStringContainsString('Team Earnings: fallback ratio warning', $page);
    $this->assertStringContainsString("'warning'", $page);
    $this->assertStringContainsString('$teamSiteFallbackWarnThreshold = 15.0;', $page);
  }

  #[Test]
  public function earningsPageContainsUnlinkedOnlyGuardContract(): void
  {
    $projectRoot = dirname(__DIR__, 3);
    $page = (string) file_get_contents($projectRoot . '/html/earnings/index.php');
    $css = (string) file_get_contents($projectRoot . '/html/css/earnings/index.php');

    $this->assertStringContainsString('earnings.team.site_resolution.unlinked_only_warn', $page);
    $this->assertStringContainsString('Team Earnings: unlinked-only guard', $page);
    $this->assertStringContainsString('$teamUnlinkedOnlyWarn = $teamUnlinkedOnlyCount > 0 && $teamMatchedTotalForSignal === 0;', $page);
    $this->assertStringContainsString('EARNINGS_TEAM_UNLINKED_ONLY_GUARD', $page);
    $this->assertStringContainsString('et_empty_guard et_empty_guard--warning', $page);

    $this->assertStringContainsString('.et_empty_guard', $css);
    $this->assertStringContainsString('.et_empty_guard--warning', $css);
  }
}
