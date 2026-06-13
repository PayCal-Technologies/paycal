<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Observability\Lens;

/**
 * Build team earnings rollup data for $selectedOrgId and $teamEarningsYear.
 *
 * Expects $selectedOrgId, $teamEarningsYear. Sets team rollup variables used by team_earnings_panel.php.
 */

/** @var list<array{name: string, uuid: string, role: string, reg_hours: float, ot_hours: float, gross: float}> */
$teamEarningsRows = [];
$teamEarningsTotals = ['reg_hours' => 0.0, 'ot_hours' => 0.0, 'gross' => 0.0, 'net' => 0.0];
$teamEarningsYear = (int) ($teamEarningsYear ?? (InputSanitizer::getString('year') ?: date('Y')));
/** @var array<string, int> $teamSiteMatchStats */
$teamSiteMatchStats = [
  'match_owner_and_site' => 0,
  'match_unique_site_id' => 0,
  'match_site_name'      => 0,
  'included_unlinked'    => 0,
];
/** @var list<array{work_key: string, member_uuid: string, site_id: string, site_owner_uuid: string, site_name: string}> $teamSiteDropSamples */
$teamSiteDropSamples = [];
$teamSiteDropWarnThreshold = 10;
$teamSiteFallbackWarnThreshold = 15.0;
$teamSiteFallbackWarn = false;
$teamUnlinkedOnlyWarn = false;
$teamUnlinkedOnlyCount = 0;

$orgSiteData_     = [];
$memberLoaTotals_ = [];
$memberWeeklyH_   = [];
$memberDays_      = [];

if (($selectedOrgId ?? '') !== '') {
  Lens::timeStart('Team Earnings: resolve snapshot');
  $cachedSnapshot = BusinessWorkspaceCache::getTeamEarnings($selectedOrgId, $teamEarningsYear);
  if ($cachedSnapshot !== null) {
    Lens::add('Team Earnings: snapshot source', ['source' => 'workspace_cache']);
    TeamEarningsSnapshotBuilder::applySnapshot($cachedSnapshot);
  } else {
    Lens::add('Team Earnings: snapshot source', ['source' => 'live_build']);
    $snapshot = TeamEarningsSnapshotBuilder::build($selectedOrgId, $teamEarningsYear);
    BusinessWorkspaceCache::putTeamEarnings($selectedOrgId, $teamEarningsYear, $snapshot);
    TeamEarningsSnapshotBuilder::applySnapshot($snapshot);
  }
  Lens::timeEnd('Team Earnings: resolve snapshot');
}
